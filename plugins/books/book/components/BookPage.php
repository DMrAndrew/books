<?php

namespace Books\Book\Components;

use Books\Book\Classes\DownloadService;
use Books\Book\Classes\Enums\ElectronicFormats;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Classes\Exceptions\DownloadNotAllowed;
use Books\Book\Classes\Traits\InjectBookStuff;
use Books\Book\Models\Book;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Books\Comments\Components\Comments;
use Books\Reposts\Components\Reposter;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Log;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Request;

/**
 * BookPage Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BookPage extends ComponentBase
{
    use InjectBookStuff;

    protected ?Book $book;

    protected ?User $user;

    protected ?int $book_id;

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

    public function init(): void
    {
        $this->user = Auth::getUser();
        $this->book_id = is_numeric($this->param('book_id'))
            ? (int) $this->param('book_id')
            : abort(404);
        $this->book = Book::findForPublic($this->book_id, $this->user);
        $this->tryInjectAdultModal();
        $this->user?->library($this->book)->get(); //Добавить в библиотеку
        $this->book = Book::query()
            ->withChapters()
            ->defaultEager()
            ->with(['cycle' => fn ($cycle) => $cycle->booksEager()])
            ->find($this->book->id);

        if ($this->book?->profile) {
            $comments = $this->addComponent(Comments::class, 'comments');
            $comments->bindModel($this->book);
            $comments->bindModelOwner($this->book->profile);
        }

        $this->addComponent(SaleTagBlock::class, 'SaleTagBlock');

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
        $this->addMeta();

        $this->registerBreadcrumbs();

        $this->setSEO();
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
            'supportBtn' => $this->supportBtn(),
            'book' => $this->book,
            'cycle' => $this->book->cycle,
            'download_btn' => $this->book->ebook->isDownloadAllowed(),
        ];
    }

    public function buyBtn(): bool
    {
        /**
         * Авторизованным пользователям
         */
        if ($this->user) {
            /**
             * Автор не может купить
             */
            if ($this->book->profiles()->user($this->user)->exists()) {
                return false;
            }

            /**
             * Уже куплена
             */
            if ($this->book->ebook->isSold($this->user)) {
                return false;
            }
        }

        /**
         * Книга бесплатная
         */
        if ($this->book->ebook->isFree()) {
            return false;
        }

        return true;
    }

    public function readBtn(): bool
    {
        return $this->book->ebook->isFree()
            || ($this->user && $this->book->ebook->isSold($this->user))
            || $this->book->ebook->chapters->some->isFree();
    }

    /**
     * Запретить поддерживать автора книги где он сам является автором
     * свои профили поддерживать нельзя
     */
    private function supportBtn(): bool
    {
        return $this->user && ! $this->book->profiles()->user($this->user)->exists();
    }

    /**
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);
        $manager->register('book', function (BreadcrumbsGenerator $trail, $params) {

            /** Главная */
            $trail->parent('home');

            /** Книги */
            $trail->push('Книги', url('/listing'));

            /** Жанр */
            $genre = $this->book->genres->first();
            if ($genre) {
                $trail->push($genre->name, url('/listing?genre='.$genre->id));
            }

            /** Название книги */
            $trail->push($this->book->title);
        });
    }

    private function setSEO(): void
    {
        $this->page->og_type = 'book';
        $this->page->meta_canonical = Request::url();

        if ($this->book->meta_title) {
            $this->page->meta_title = $this->book->meta_title;
        }

        if ($this->book->meta_desc) {
            $this->page->meta_description = $this->book->meta_desc;
        }
    }

    public function onDownload()
    {
        try {
            $format = ElectronicFormats::tryFrom(post('format')) ?? ElectronicFormats::default();
            if (! $this->book->ebook->isDownloadAllowed()) {
                throw new DownloadNotAllowed();
            }

            $h = ['Content-Description' => 'File Transfer'];
            $file = (new DownloadService($this->book, $format))->getFile();

            ob_get_clean();
            return \Response::download($file->getLocalPath(), $file->getFilename(), $h);
        } catch (Exception $exception) {
            Flash::error($exception->getMessage());
            Log::error($exception->getMessage());

            return [];
        }

    }
}
