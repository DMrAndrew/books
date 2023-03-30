<?php

namespace Books\Book\Components;

use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Models\Book;
use Books\Comments\Components\Comments;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * BookPage Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BookPage extends ComponentBase
{
    protected Book $book;

    protected ?User $user;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'BookPage Component',
            'description' => 'No description provided yet...',
        ];
    }

    /**
     * defineProperties for the component
     *
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->user = Auth::getUser();
        $book_id = $this->param('book_id');
        $this->book = Book::query()->public()->find($book_id) ?? $this->user?->profile->books()->find($book_id)
            ?? abort(404);
        $this->user?->library($this->book)->get(); //Добавить в библиотеку
        $this->book = Book::query()
            ->defaultEager()
            ->withChapters()
            ->with(['cycle' => fn ($cycle) => $cycle->booksEager()])
            ->find($this->book->id);

        $this->page['book'] = $this->book;
        $this->page['cycle'] = $this->book->cycle;
        $comments = $this->addComponent(Comments::class, 'comments');
        $comments->bindModel($this->book);
        $comments->bindModelOwner($this->book->profile);

        $otherAuthorBook = $this->addComponent(Widget::class, 'otherAuthorBook');
        $otherAuthorBook->setUpWidget(WidgetEnum::otherAuthorBook, book: $this->book, withHeader: false);

        $with = $this->addComponent(Widget::class, 'with_this');
        $with->setUpWidget(WidgetEnum::readingWithThisOne, book: $this->book, withHeader: false);

        $hot_new = $this->addComponent(Widget::class, 'hotNew');
        $hot_new->setUpWidget(WidgetEnum::hotNew, withHeader: false);

        $popular = $this->addComponent(Widget::class, 'popular');
        $popular->setUpWidget(WidgetEnum::popular, book: $this->book, withHeader: false);

        $cycle = $this->addComponent(Widget::class, 'cycle_widget');
        $cycle->setUpWidget(WidgetEnum::cycle, book: $this->book, withHeader: false);

        $recommend = $this->addComponent(Widget::class, 'recommend');
        $recommend->setUpWidget(WidgetEnum::recommend, short: true);
    }
}
