<?php namespace Books\Book\Components;

use Books\Book\Models\BookStatus;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;
use Redirect;
use Request;

/**
 * ECommerceBooker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class ECommerceBooker extends ComponentBase
{
    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'ECommerceBooker Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function onRun()
    {
        $this->page['bookStatusCases'] = BookStatus::publicCases();
    }

    public function onUpdateEcommerce()
    {
        try {
            if ($book = Auth::getUser()?->books()->find(post('book_id'))) {
                $data = post();
                $data['sales_free'] = !!$data['sales_free'];
                $book->update($data);
                $book->status = $data['status'];
                $book->save();
            }
            return Redirect::refresh();
        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
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
}
