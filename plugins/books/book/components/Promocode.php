<?php namespace Books\Book\Components;

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
        $data = post();
        $book = Book::find($data['value']);

        $promocodes = $book ? $this->getBooksPromocodes($book) : [];

        return [
            '#promocodesList' => $this->renderPartial('promocode/list', ['promocodes' => $promocodes])
        ];
    }

    public function onGenerate()
    {
        $data = post();

        if ( !isset($data['book_id']) || empty($data['book_id'])) {
            Flash::error("Необходимо выбрать книгу для генерации промокода");

            return;
        }

        try {
            /**
             * current user is book author
             */
            $book = Book::findOrFail($data['book_id']);
            $allBookAuthorsProfilesIds = $book->authors->pluck('profile_id')->toArray();
            $currentAuthorProfileId = $this->user->profile->id;
            if ( !in_array($currentAuthorProfileId, $allBookAuthorsProfilesIds)) {
                Flash::error("Вы не являетесь автором этой книги");

                return;
            }

            /**
             * check promocode limits
             */
            // todo

            /**
             * generate promocode
             */
            PromocodeModel::create([
                'book_id' => Book::findOrFail($data['book_id'])?->id,
                'profile_id' => $this->user->profile?->id,
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

    public function getBooksPromocodes(Book $book): Collection
    {
        return PromocodeModel
            ::with(['user'])
            ->where('book_id', $book->id)
            ->get();
    }
}
