<?php namespace Books\Book\Components;

use Books\Book\Models\Chapter;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * Chapterer Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Chapterer extends ComponentBase
{
    protected $book_id;
    protected $chapter_id;
    protected $user;
    protected $book;
    protected $chapter;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'Chapterer Component',
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
        $this->user = Auth::getUser();
        $this->book = $this->user?->books()->find($this->param('book_id'));
        $this->chapter = $this->book?->chapters()->find($this->param('chapter_id')) ?? new Chapter();
    }

    public function onRun()
    {
        $this->prepareVals();
    }

    public function prepareVals()
    {
        $this->page['book'] = $this->book;
        $this->page['chapter'] = $this->chapter;
    }
}
