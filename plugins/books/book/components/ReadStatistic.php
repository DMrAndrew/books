<?php

namespace Books\Book\Components;

use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\StatisticService;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * ReadStatistic Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class ReadStatistic extends ComponentBase
{
    protected User $user;

    protected ?Book $book;

    protected ?Chapter $chapter;

    protected StatisticService $service;

    protected Carbon $from;

    protected Carbon $to;

    public function componentDetails()
    {
        return [
            'name' => 'ReadStatistic Component',
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
        if ($r = redirectIfUnauthorized()) {
            return $r;
        }
        $dates = explode('-', post('dates') ?? request()->query('dates'));
        if (isset($dates[0]) && isset($dates[1])) {
            $this->from = Carbon::parse($dates[0]);
            $this->to = Carbon::parse($dates[1]);
        } else {
            $this->to = today();
            $this->from = $this->to->copy()->subWeeks(2);
        }
        $this->service = new StatisticService($this->from, $this->to);

        if ($this->param('book_id')) {
            $this->service->setClass(Chapter::class);
        }
        $this->user = Auth::getUser();
        $this->book = $this->user->profile
            ->books()
            ->editionTypeIn(EditionsEnums::Ebook)
            ->find($this->param('book_id') ?? post('book_id'));
    }

    public function onRender()
    {
        $this->prepareVals();
    }

    public function isParts()
    {
        return (bool) $this->param('book_id');
    }

    public function prepareVals()
    {
        if ($this->isParts()) {
            $this->service->setClass(Chapter::class);
            $item = $this->book->chapters()->get();
            $this->page->meta_title = $this->page->meta_title.' - '.$this->book->title;
        } else {
            $item = $this->book ? [$this->book] : $this->user->profile->books()->get();
        }

        $this->page['from'] = $this->from->format('d.m.Y');
        $this->page['to'] = $this->to->format('d.m.Y');
        $this->page['books'] = $this->user->profile->books()
            ->editionTypeIn(EditionsEnums::Ebook)
            ->get();
        $this->page['current_book'] = $this->book;
        $this->page['statistic'] = $this->service->get(...$item);
    }

    public function onCount()
    {
        $this->prepareVals();

        return [
            '#statistic_spawn' => $this->renderPartial($this->isParts() ? 'parts/default' : '@default'),
        ];
    }
}
