<?php

namespace Books\Book\Components;

use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Classes\Reader as Service;
use Books\Book\Classes\Traits\InjectBookStuff;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;

/**
 * Reader Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Reader extends ComponentBase
{
    use InjectBookStuff;

    protected ?Book $book;

    protected ?Chapter $chapter;

    protected ?User $user;

    protected ?Service $service = null;

    protected int $book_id;

    protected int $chapter_id;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'Reader Component',
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
        $this->book_id = (int) $this->param('book_id') ?? abort(404);
        $this->book = Book::findForPublic($this->book_id, $this->user);
        $this->chapter_id = (int) $this->param('chapter_id');
        $this->chapter = $this->chapter_id ? Chapter::public()->withLength()->find($this->chapter_id)
            ?? abort(404) : null;
        $this->tryInjectAdultModal();
        $this->addMeta();
        $recommend = $this->addComponent(Widget::class, 'recommend');
        $recommend->setUpWidget(WidgetEnum::recommend, short: true);
        $advert = $this->addComponent(AdvertBanner::class, 'advertBanner');

        $this->registerBreadcrumbs();
    }

    public function onRun()
    {
        if (! $this->chapter) {
            if ($paginator = $this->getLastTrackedPaginator()) {
                return Redirect::to(sprintf('/reader/%s/%s/%s', $this->book->id, $paginator->chapter_id, $paginator->page));
            }
        }

        if (! $this->service()->isPageAllowed()) {
            return Redirect::to(sprintf('/out-of-free/%s/%s', $this->book->ebook->id, $this->chapter?->id));
        }
    }

    public function onRender()
    {
        $this->prepareVals();
    }

    public function getLastTrackedPaginator()
    {
        return $this->book
            ->paginationTrackers()
            ->latestActiveTracker()
            ->first()?->trackable;
    }

    public function service(): Service
    {
        if (! $this->service) {
            $this->service = new Service(
                book: $this->book,
                chapter: $this->chapter,
                page: $this->getPageKey(),
                user: $this->user
            );
        }

        return $this->service;
    }

    public function prepareVals()
    {
        foreach ($this->service()->getReaderPage() as $key => $item) {
            $this->page[$key] = $item;
        }
        $this->page['user'] = $this->user;
    }

    public function onNext()
    {
        if ($paginator = $this->service()->nextPage()) {
            $this->service()->setPage($paginator->page);

            return $this->onMove();
        }
        if ($chapter = $this->service()->nextChapter()) {
            return Redirect::to(sprintf('/reader/%s/%s', $this->book->id, $chapter->id));
        }
        if ($this->service()->readBtn()) {
            $this->user->library($this->book)->read();

            return Redirect::to(sprintf('/book-card/%s', $this->book->id));
        }

        return false;
    }

    public function onPrev()
    {
        if ($paginator = $this->service()->prevPage()) {
            $this->service()->setPage($paginator->page);

            return $this->onMove();
        }
        if ($chapter = $this->service()->prevChapter()) {
            return Redirect::to(sprintf('/reader/%s/%s', $this->book->id, $chapter->id));
        }

        return false;
    }

    public function onChapter()
    {
        if ($chapter = post('value')) {
            return Redirect::to(sprintf('/reader/%s/%s', $this->book->id, $chapter));
        }
    }

    public function onMove()
    {

        $this->prepareVals();

        return [
            '#reader-body-spawn' => $this->renderPartial('@body'),
            '.reader-user-section' => $this->renderPartial('@user-section'),
        ];
    }

    public function onTrack()
    {
        return $this->service()->track((int) post('ms'), (int) post('paginator_id'));
    }

    public function getPageKey(): ?int
    {
        $page = $this->getCurrentPaginatorKey() ?? $this->getParamPage();

        return is_null($page) ? $page : ((int) $page);
    }

    public function getCurrentPaginatorKey()
    {
        return post('paginator_page');
    }

    public function getParamPage()
    {
        return $this->param('page');
    }

    /**
     * @return void
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);
        $manager->register('reader', function (BreadcrumbsGenerator $trail, $params) {

            /** Главная */
            $trail->parent('home');

            /** Книги */
            $trail->push('Книги', url('/listing'));

            /** Жанр */
            $genre = $this->book->genres->first();
            if ($genre) {
                $trail->push($genre->name, url('/listing?genre=' . $genre->id));
            }

            /** Название книги */
            $trail->push($this->book->title, url('/book-card/' . $this->book->id));
        });
    }
}
