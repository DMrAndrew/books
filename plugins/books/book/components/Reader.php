<?php namespace Books\Book\Components;

use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Pagination;
use Cms\Classes\ComponentBase;
use Cookie;
use RainLab\User\Facades\Auth;
use Response;

/**
 * Reader Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Reader extends ComponentBase
{
    protected Book $book;
    protected Chapter $chapter;
    protected Pagination $paginator;
    protected $chapters;
    protected $pagination;
    protected ?int $currenPage = null;

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

        $book_id = $this->param('book_id');
        $this->book = Book::query()->public()->find($book_id)
            ?? Auth::getUser()->profile->books()->find($book_id)
            ?? abort(404);
        $this->page['book'] = $this->book;
        $this->chapters = $this->book->ebook?->chapters ?? abort(404);
        $this->prepareVals();

    }

    public function prepareVals()
    {
        $this->page['chapters'] = $this->chapters;
        $this->chapter = $this->chapters->first(fn($i) => $i->id == $this->param('chapter_id')) ?? $this->chapters->first();
        $this->pagination = $this->chapter->pagination;
        $this->paginator = $this->pagination->first(fn($i) => $i->page == $this->getCurrentPaginatorKey());
        $this->page['pagination'] = [
            'prev' => $this->getPrevPage() ?? $this->getPrevChapter(),
            'links' => $this->chapter->getPaginationLinks($this->getCurrentPaginatorKey()),
            'next' => $this->getNextPage() ?? $this->getNextChapter()
        ];
        $this->page['reader'] = [
            'chapter' => $this->chapter,
            'paginator' => $this->paginator,
        ];
    }

    public function getNextPage()
    {
        $index = $this->pagination->search(fn($i) => $i->page == $this->getCurrentPaginatorKey());
        return $this->pagination[$index + 1] ?? null;
    }

    public function getPrevPage()
    {
        $index = $this->pagination->search(fn($i) => $i->page == $this->getCurrentPaginatorKey());
        return $this->pagination[$index - 1] ?? null;
    }

    public function getNextChapter()
    {
        $index = $this->chapters->search(fn($i) => $i->id == $this->chapter->id);
        return $this->chapters[$index + 1] ?? null;
    }

    public function getPrevChapter()
    {
        $index = $this->chapters->search(fn($i) => $i->id == $this->chapter->id);
        return $this->chapters[$index - 1] ?? null;
    }

    public function onNext()
    {
        if ($page = $this->getNextPage()) {
            $this->currenPage = $page->page;
            return $this->onMove();
        }
        if ($chapter = $this->getNextChapter()) {
            return \Redirect::to('/reader/' . $this->book->id . '/' . $chapter->id);
        }
        return false;
    }

    public function onPrev()
    {
        if ($page = $this->getPrevPage()) {
            $this->currenPage = $page->page;
            return $this->onMove();
        }
        if ($chapter = $this->getPrevChapter()) {
            return \Redirect::to('/reader/' . $this->book->id . '/' . $chapter->id);
        }
        return false;
    }


    public function onMove()
    {
        $this->prepareVals();
        return Response::make([
            '#reader-body-spawn' => $this->renderPartial('@body')
        ]);

    }

    public function getCurrentPaginatorKey()
    {
        return $this->currenPage ?? post('paginator_page') ?? 1;
    }

    public function makeCookie()
    {
        return Cookie::make('reader_last_visited', json_encode([
            'chapter_id' => $this->chapter->id,
            'paginator_id' => $this->chapter->id,
        ]));
    }
}
