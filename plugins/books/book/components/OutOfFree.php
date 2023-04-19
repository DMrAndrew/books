<?php

namespace Books\Book\Components;

use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Cms\Classes\ComponentBase;
use RainLab\User\Models\User;

/**
 * OutOfFree Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class OutOfFree extends ComponentBase
{
    protected Book $book;

    protected ?Chapter $chapter;

    protected ?User $user;

    public function componentDetails()
    {
        return [
            'name' => 'OutOfFree Component',
            'description' => 'No description provided yet...',
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
        $this->book = Book::query()->public()->find($this->param('book_id')) ?? abort(404);
        $this->chapter = Chapter::find($this->param('chapter_id'));
        $this->page['book'] = $this->book;
        $this->page['chapter'] = $this->chapter;
    }
}
