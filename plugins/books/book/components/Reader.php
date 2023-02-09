<?php namespace Books\Book\Components;


use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Response;
use \Books\Book\Classes\Reader as Service;

/**
 * Reader Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Reader extends ComponentBase
{
    protected Book $book;
    protected User $user;
    protected Service $service;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'Reader Component',
            'description' => 'No description provided yet...'
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
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }

        $this->user = Auth::getUser();
        $this->book = Book::query()->public()->find($this->param('book_id'))
            ?? $this->user?->profile->books()->find($this->param('book_id')) ?? abort(404);
        $this->service = new Service(
            book: $this->book,
            chapter: Chapter::find($this->param('chapter_id')),
            page: $this->getCurrentPaginatorKey()
        );

        $this->prepareVals();

    }

    public function prepareVals()
    {
        foreach ($this->service->getReaderPage() as $key => $item) {
            $this->page[$key] = $item;
        }
    }


    public function onNext()
    {
        if ($page = $this->service->nextPage()) {
            $this->service->setPage($page);
            return $this->onMove();
        }
        if ($chapter = $this->service->nextChapter()) {
            return \Redirect::to('/reader/' . $this->book->id . '/' . $chapter);
        }
        //add to library
        return false;
    }

    public function onPrev()
    {
        if ($page = $this->service->prevPage()) {
            $this->service->setPage($page);
            return $this->onMove();
        }
        if ($chapter = $this->service->prevChapter()) {
            return \Redirect::to('/reader/' . $this->book->id . '/' . $chapter);
        }
        return false;
    }

    public function onChapter(){
        if($chapter = post('value')){
            return \Redirect::to('/reader/' . $this->book->id . '/' . $chapter);
        }
    }


    public function onMove()
    {
        $this->prepareVals();
        return Response::make([
            '#reader-body-spawn' => $this->renderPartial('@body')
        ]);

    }

    public function onTrack()
    {
        return $this->service->track((int)post('ms'));
    }

    public function getCurrentPaginatorKey()
    {
        return post('paginator_page');
    }
}
