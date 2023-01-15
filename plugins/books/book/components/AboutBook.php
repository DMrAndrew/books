<?php namespace Books\Book\Components;

use ApplicationException;
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
        $this->user = Auth::getUser() ?? throw new ApplicationException('User required');
        $this->book_id = (int)$this->param('book_id');
        $this->book = $this->user->profile->books()->with('editions')->find($this->book_id) ?? throw new ApplicationException('Book not found');

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
        $this->prepareVals();
    }

    function prepareVals()
    {
       $this->page['book'] = $this->book;
    }
}
