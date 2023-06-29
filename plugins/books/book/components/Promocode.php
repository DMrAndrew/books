<?php namespace Books\Book\Components;

use Books\Book\Classes\PromocodeGenerationLimiter;
use Books\Book\Models\Author;
use Books\Book\Models\Book;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Database\Eloquent\Collection;
use Log;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Promocode Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Promocode extends ComponentBase
{
    protected User $user;

    protected ?Book $book;

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

        $this->book = $this->getBook();
    }

    public function onRender()
    {
        foreach ($this->vals() as $key => $val) {
            $this->page[$key] = $val;
        }
    }

    public function vals()
    {
        return [
            'books' => $this->getNotFreeAccountBooks(),
            'bookItem' => $this->getBook(),
            'promocodes' => $this->getBooksPromocodes()
        ];
    }

    public function getBook()
    {
        return $this
            ->getNotFreeAccountBooks()
            ->find(post('value') ?? post('book_id') ?? $this->param('book_id'));
    }

    public function onGetBookPromocodes()
    {
        return [
            '#promocodesList' => $this->renderPartial('promocode/list', ['promocodes' => $this->getBooksPromocodes()])
        ];
    }

    public function onGenerate()
    {
        try {

            /**
             * current user is book author
             */
            if (!$this->book) {
                Flash::error("Книга не найдена");

                return [];
            }

            /**
             * check promocode limits
             */
            $promoLimiter = new PromocodeGenerationLimiter(profile: $this->user->profile, book: $this->book);
            if (!$promoLimiter->checkCanGenerate()) {
                Flash::error($promoLimiter->getReason());

                return [];
            }

            /**
             * generate promocode
             */
            $this->book->ebook->promocodes()->create([
                'profile_id' => $this->user->profile->id,
                'expire_in' => $promoLimiter->getExpireIn(),
            ]);

            Flash::success('Новый промокод сгенерирован');

            return [
                '#promocodesList' => $this->renderPartial('promocode/list', ['promocodes' => $this->getBooksPromocodes()])
            ];

        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            Flash::error($ex->getMessage());
            return [];
        }
    }

    /**
     * @return Collection|null
     */
    private function getBooksPromocodes(): ?Collection
    {
        return $this->book?->ebook?->promocodes()
            ->with(['user'])
            ->get() ?? new Collection();
    }

    /**
     * @return Collection
     */
    private function getNotFreeAccountBooks(): Collection
    {
        $allAccountProfilesIds = $this->user->profiles->pluck('id')->toArray();
        $booksIds = Author
            ::with(['book'])
            ->whereIn('profile_id', $allAccountProfilesIds)
            ->get()
            ->pluck('book_id')
            ->toArray();

        return Book::whereIn('id', $booksIds)->notFree()->get();
    }
}
