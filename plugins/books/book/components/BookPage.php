<?php

namespace Books\Book\Components;

use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Models\Book;
use Books\Comments\Components\Comments;
use Books\Reposts\Components\Reposter;
use Books\User\Classes\CookieEnum;
use Books\User\Classes\UserService;
use Cms\Classes\ComponentBase;
use Cookie;
use Log;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\Settings;
use RainLab\User\Models\User;
use Redirect;

/**
 * BookPage Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BookPage extends ComponentBase
{
    protected ?Book $book;

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
        $this->book = Book::query()->public()->find($book_id) ?? $this->user?->profile->books()->find($book_id);

        if (!$this->book && $book_id && Book::find($book_id)?->isAdult()) {
            $this->book = Book::find($book_id);
            UserService::canBeAskedAdultPermission() ? ($this->page['ask_adult'] = 1) : abort(404);
        }
        if (!$this->book) {
            abort(404);
        }
        $this->user?->library($this->book)->get(); //Добавить в библиотеку
        $this->book = Book::query()
            ->withChapters()
            ->defaultEager()
            ->with(['cycle' => fn($cycle) => $cycle->booksEager()])
            ->find($this->book->id);

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


        $reposts = $this->addComponent(Reposter::class, 'reposts');
        $reposts->bindSharable($this->book);

        $this->addComponent(BookAwards::class, 'bookAwards');
        $this->addComponent(AdvertBanner::class, 'advertBanner');
        $this->page->meta_title = '«' . $this->book->title . '»';
        $this->page->meta_preview = $this->book->cover->path;
        $this->page->meta_description = strip_tags($this->book->annotation);
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
            'buyBtn' => $this->buyBtn(),
            'readBtn' => $this->readBtn(),
            'book' => $this->book,
            'cycle' => $this->book->cycle
        ];
    }

    public function buyBtn()
    {
        return $this->user && !$this->book->ebook->isSold($this->user) && !$this->book->ebook->isFree();
    }

    public function readBtn()
    {
        if (!$this->user) {
            return $this->book->ebook->isFree()
                || $this->book->ebook->chapters->some(fn($i) => $i->isFree());
        }

        return $this->book->ebook->isSold($this->user)
            || $this->book->ebook->isFree()
            || $this->book->ebook->chapters->some(fn($i) => $i->isFree());
    }
}
