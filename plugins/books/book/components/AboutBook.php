<?php

namespace Books\Book\Components;

use Books\Book\Models\Book;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * AboutBook Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class AboutBook extends ComponentBase
{
    protected ?Book $book;

    protected User $user;

    protected int $book_id;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'AboutBook Component',
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
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser();
        $this->book_id = (int) $this->param('book_id');
        $this->book = $this->user->profile
            ->books()
            ->with(['editions' => fn ($q) => $q->withPriceEager()])
            ->findOrFail($this->book_id);

        $this->addComponent(
            Booker::class,
            'booker',
            [
                'book_id' => $this->book->id,
                'user_id' => $this->user->id,
            ]
        );
        $this->addComponent(
            EBooker::class,
            'ebooker',
            [
                'book_id' => $this->book->id,
            ]
        );
        $this->addComponent(
            AudioBooker::class,
            'audiobooker',
            [
                'book_id' => $this->book->id,
            ]
        );
        $this->prepareVals();
    }

    public function prepareVals()
    {
        $this->page['book'] = $this->book;
        $this->page->meta_title = $this->page->meta_title.' '.$this->book->title;
    }
}
