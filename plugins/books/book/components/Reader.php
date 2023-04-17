<?php

namespace Books\Book\Components;

use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Classes\Reader as Service;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
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
    protected Book $book;

    protected ?Chapter $chapter;

    protected ?User $user;

    protected Service $service;

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
        $this->book = Book::query()->public()->find($this->param('book_id'))
            ?? $this->user?->profile->books()->find($this->param('book_id')) ?? abort(404);
        $this->chapter = $this->param('chapter_id') ? Chapter::find($this->param('chapter_id')) ?? abort(404) : null;
        $this->service = new Service(
            book: $this->book,
            chapter: $this->chapter,
            page: $this->getCurrentPaginatorKey(),
            user: $this->user
        );

        $recommend = $this->addComponent(Widget::class, 'recommend');
        $recommend->setUpWidget(WidgetEnum::recommend, short: true);
    }

    public function onRun()
    {
        if (! $this->service->isPageAllowed()) {
            return Redirect::to('/out-of-free/'.$this->book->id.'/'.$this->chapter?->id);
        }
    }

    public function onRender()
    {
        $this->prepareVals();
    }

    public function prepareVals()
    {
        foreach ($this->service->getReaderPage() as $key => $item) {
            $this->page[$key] = $item;
        }
        $this->page['user'] = $this->user;
    }

    public function onNext()
    {
        if ($paginator = $this->service->nextPage()) {
            $this->service->setPage($paginator->page);

            return $this->onMove();
        }
        if ($chapter = $this->service->nextChapter()) {
            return Redirect::to('/reader/'.$this->book->id.'/'.$chapter->id);
        }
        if ($this->service->readBtn()) {
            $this->user->library($this->book)->read();

            return Redirect::to('/book-card/'.$this->book->id);
        }

        return false;
    }

    public function onPrev()
    {
        if ($paginator = $this->service->prevPage()) {
            $this->service->setPage($paginator->page);

            return $this->onMove();
        }
        if ($chapter = $this->service->prevChapter()) {
            return Redirect::to('/reader/'.$this->book->id.'/'.$chapter->id);
        }

        return false;
    }

    public function onChapter()
    {
        if ($chapter = post('value')) {
            return Redirect::to('/reader/'.$this->book->id.'/'.$chapter);
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
        return $this->service->track((int) post('ms'), (int) post('paginator_id'));
    }

    public function getCurrentPaginatorKey()
    {
        return post('paginator_page');
    }
}
