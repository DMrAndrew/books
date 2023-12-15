<?php

namespace Books\Book\Components;

use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
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
    protected Edition $edition;

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
        $this->edition = Edition::query()
            ->whereHas('book', function ($query) {
                return $query->public();
            })
            ->findOrFail($this->param('edition_id')) ?? abort(404);

        $this->chapter = Chapter::find($this->param('chapter_id'));

        $this->page['edition'] = $this->edition;
        $this->page['chapter'] = $this->chapter;
    }
}
