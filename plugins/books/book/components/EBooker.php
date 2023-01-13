<?php namespace Books\Book\Components;


use Books\Book\Models\Book;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * EBooker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class EBooker extends ComponentBase
{
    protected int $book_id;
    protected Book $book;
    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'EBooker Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function init()
    {
        $this->book_id = (int)$this->param('book_id');
        $this->book = Auth::getUser()->books()->find($this->book_id);
        parent::init();
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

    public function onUpdateSortOrder()
    {
        $sequence = post('sequence');
        $this->book->changeChaptersOrder($sequence);
        return [
            '#ebooker-content' => $this->renderPartial('@default',['book' => $this->book])
        ];
    }
}
