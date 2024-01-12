<?php namespace Books\Book\Components;

use Books\Book\Models\Book;
use Books\Collections\classes\CollectionEnum;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * BookCard Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BookCard extends ComponentBase
{
    protected ?Book $book;
    protected ?User $user;

    public function componentDetails()
    {
        return [
            'name' => 'BookCard Component',
            'description' => 'No description provided yet...'
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
        parent::init();
        $this->addComponent(SaleTagBlock::class, 'SaleTagBlock');
    }

    public function acceptable(): bool
    {
        $this->user = Auth::getUser();
        $this->book = Book::find(post('book_id'));
        return $this->user && $this->book;
    }

    public function onAddLib()
    {
        if (!$this->acceptable()) {
            return $this->render();
        }
        $library = $this->user->library($this->book);
        $library->when($library->has() && !$library->is(CollectionEnum::WATCHED),
            fn($lib) => $lib->remove(),
            fn($lib) => $lib->get() && $lib->interested());

        $this->book->rater()->libs()->run();

        return $this->render();
    }

    public function onLike()
    {
        if (!$this->acceptable()) {
            return $this->render();
        }

        $this->user->toggleFavorite($this->book);
        $this->book->rater()->likes()->run();
        $this->book->rater()->rate()->queue();

        return $this->render();
    }

    public function render(array $options = [])
    {
        if ($partial = request()->header('X-OCTOBER-REQUEST-PARTIAL')) {
            return [
                $partial => $this->renderPartial($partial,
                    ['book' => Book::query()->defaultEager()->find($this->book->id), ...$options])
            ];
        }

    }


}
