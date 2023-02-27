<?php namespace Books\Book\Components;

use Books\Book\Classes\StatisticService;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
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

    public function componentDetails()
    {
        return [
            'name' => 'ReadStatistic Component',
            'description' => 'No description provided yet...'
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
        $this->service = new StatisticService();
        $this->user = Auth::getUser();
        $books = $this->user->profile->books;
        $this->page['books'] = $books;
        $this->page['statistic'] = $this->service->get(...$books);
        $this->page['dates'] = $this->service->getDates();

    }

}
