<?php

namespace Books\Book\Components;

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
        $this->cycle = CycleModel::query()
            ->booksEager()
            ->findOrFail((int)$this->param('cycle_id'));

        $books = $this->cycle->books;
        $this->page['cycle'] = $this->cycle;
        $this->page['books'] = $books;
        $this->page->meta_title = $this->page->meta_title . ' «' . $this->cycle->name . '»';
        $this->page['start_at'] = $books->first()?->editions()->first()?->sales_at?->format('d.m.y') ?? '-';
        $this->page['end_at'] = $books->last()?->editions()->first()?->sales_at?->format('d.m.y') ?? '-';
        $this->page['last_updated_at'] = $this->cycle->last_updated_at?->format('d.m.y') ?? '-';

        $this->setSEO();
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    /**
     * @return void
     */
    private function setSEO(): void
    {
        $this->page->meta_description = sprintf(
            'Перечень произведений из цикла «%s»',
            $this->cycle->name
        );
    }
}
