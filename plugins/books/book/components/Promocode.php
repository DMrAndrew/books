<?php namespace Books\Book\Components;

use Books\Book\Classes\PromocodeGenerationLimiter;
use Books\Book\Models\Book;
use Books\Book\Models\Promocode as PromocodeModel;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Database\Eloquent\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Request;

/**
 * Promocode Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Promocode extends ComponentBase
{
    protected User $user;

    public function componentDetails()
    {
        return [
            'name' => 'Promocode Component',
            'description' => 'Компонент промокодов'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser();

        $this->page['books'] = $this->user->profile->books()->get();
        $this->page['promocodes'] = [];
    }

    public function onGetBookPromocodes()
    {

        $book = $this->user->profile
            ->books()
            ->find(post('value'));

        $promocodes = $book ? $this->getBooksPromocodes($book) : [];

        return [
            '#promocodesList' => $this->renderPartial('promocode/list', ['promocodes' => $promocodes])
        ];
    }

    public function onGenerate()
    {
        try {
            $book = $this->user->profile
                ->books()
                ->find(post('book_id'));

            /**
             * current user is book author
             */
            if (!$book) {
                Flash::error("Книга не найдена");

                return [];
            }

            /**
             * check promocode limits
             */
            $promoLimiter = new PromocodeGenerationLimiter(profile: $this->user->profile, book: $book);
            if (!$promoLimiter->checkCanGenerate()) {
                Flash::error($promoLimiter->getReason());

                return [];
            }

            /**
             * generate promocode
             */
            $book->ebook->promocodes()->create([
                'profile_id' => $this->user->profile?->id,
                'expire_in' => $promoLimiter->getExpireIn(),
            ]);

            Flash::success('Новый промокод сгенерирован');

            return [
                '#promocodesList' => $this->renderPartial('promocode/list', ['promocodes' => $this->getBooksPromocodes($book)])
            ];

        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    public function onActivate()
    {
        // todo реализовать после оплаты/покупки
    }

    private function getBooksPromocodes(Book $book): ?Collection
    {
        return $book->ebook?->promocodes()
            ->with(['user'])
            ->get();
    }
}
