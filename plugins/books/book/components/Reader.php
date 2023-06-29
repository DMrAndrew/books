<?php

namespace Books\Book\Components;

use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Classes\Reader as Service;
use Books\Book\Classes\Traits\InjectBookStuff;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Pagination;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;
use Response;

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
        $this->book_id = (int)$this->param('book_id') ?? abort(404);
        $this->book = Book::query()->public()->find($this->book_id)
            ?? $this->user?->profile->books()->find($this->book_id);
        $this->chapter_id = (int)$this->param('chapter_id');
        $this->chapter = $this->chapter_id ? Chapter::find($this->chapter_id) ?? abort(404) : null;
        $this->tryInjectAdultModel();
        $this->addMeta();
        $recommend = $this->addComponent(Widget::class, 'recommend');
        $recommend->setUpWidget(WidgetEnum::recommend, short: true);
        $advert = $this->addComponent(AdvertBanner::class, 'advertBanner');
    }

    public function onRun()
    {
        if (!$this->chapter) {
            if ($paginator = $this->getLastTrackedPaginator()) {
                return Redirect::to('/reader/' . $this->book->id . '/' . $paginator->chapter_id . '/' . $paginator->page);
            }
        }

        if (!$this->service()->isPageAllowed()) {
            return Redirect::to('/out-of-free/' . $this->book->id . '/' . $this->chapter?->id);
        }
    }

    public function onRender()
    {
        $this->prepareVals();
    }

    public function getLastTrackedPaginator(): ?Pagination
    {
        return $this->book
            ->paginationTrackers()
            ->userOrIpWithDefault($this->user)
            ->type(Pagination::class)
            ->orderByUpdatedAt(asc: false)
            ->first()?->trackable;
    }

    public function service(): Service
    {
        if (!$this->service) {
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
            return Redirect::to('/reader/' . $this->book->id . '/' . $chapter->id);
        }
        if ($this->service()->readBtn()) {
            $this->user->library($this->book)->read();

            return Redirect::to('/book-card/' . $this->book->id);
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
            return Redirect::to('/reader/' . $this->book->id . '/' . $chapter->id);
        }

        return false;
    }

    public function onChapter()
    {
        if ($chapter = post('value')) {
            return Redirect::to('/reader/' . $this->book->id . '/' . $chapter);
        }
    }

    public function onMove()
    {
        $this->prepareVals();

        return Response::make([
            '#reader-body-spawn' => $this->renderPartial('@body'),
            '.reader-user-section' => $this->renderPartial('@user-section'),
        ]);
    }

    public function onTrack()
    {
        return $this->service()->track((int)post('ms'), (int)post('paginator_id'));
    }

    public function getPageKey(): ?int
    {
        $page = $this->getCurrentPaginatorKey() ?? $this->getParamPage();
        return is_null($page) ? $page : ((int)$page);
    }

    public function getCurrentPaginatorKey()
    {
        return post('paginator_page');
    }

    public function getParamPage()
    {
        return $this->param('page');
    }
}
