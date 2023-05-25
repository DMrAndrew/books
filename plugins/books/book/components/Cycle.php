<?php

namespace Books\Book\Components;

use Books\Book\Models\Book;
use Books\Book\Models\Cycle as CycleModel;
use Cms\Classes\ComponentBase;

/**
 * Cycle Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Cycle extends ComponentBase
{
    protected CycleModel $cycle;

    public function componentDetails()
    {
        return [
            'name' => 'Cycle Component',
            'description' => 'No description provided yet...',
        ];
    }

    public function init()
    {
        $this->cycle = CycleModel::query()->booksEager()->find($this->param('cycle_id')) ?? abort(404);
        $this->cycle->books->count() > 0 ?: abort(404);
        $books = Book::sortCollectionBySalesAt($this->cycle->books, false)->values();
        $this->page['cycle'] = $this->cycle;
        $this->page['books'] = $books;
        $this->page->meta_title = $this->page->meta_title.' «'.$this->cycle->name.'»';
        $this->page['start_at'] = $books->first()?->ebook->sales_at?->format('d.m.y') ?? '-';
        $this->page['end_at'] = $books->last()?->ebook?->sales_at?->format('d.m.y') ?? '-';
        $this->page['last_updated_at'] = $this->cycle->last_updated_at?->format('d.m.y') ?? '-';
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }
}
