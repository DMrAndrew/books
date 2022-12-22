<?php namespace Books\Book\Components;

use ApplicationException;
use Books\Book\Classes\FB2Manager;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Input;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;
use Request;
use ValidationException;
use Validator;

/**
 * LCBooker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class LCBooker extends ComponentBase
{
    protected User $user;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'LCBooker Component',
            'description' => 'Компонент книг личного кабинета'
        ];
    }

    public function init()
    {
        $this->user = Auth::getUser();
    }

    public function onRun()
    {
        $this->page['books'] = $this->getBooks();
    }

    function getBooks()
    {
        return $this->user?->books()->get();
    }

    public function onChangeOrder()
    {
        $id = post('book_id');
        $action = post('action');
        if (!in_array($action, ['up', 'down'])) {
            return;
        }
        if ($book = $this->user?->books()->find($id)) {

            $books = $this->getBooks()->pluck('id');

            if ($action === 'up' && (int)$books->first() === (int)$id) {
                return;
            }
            if ($action === 'down' && (int)$books->last() === (int)$id) {
                return;
            }

            $ids = $books->toArray();
            $from = array_search($id, $ids);

            if ($action === 'down') {
                $temp = $ids[$from];
                $ids[$from] = $ids[$from + 1];
                $ids[$from + 1] = $temp;
            } else {
                $temp = $ids[$from];
                $ids[$from] = $ids[$from - 1];
                $ids[$from - 1] = $temp;
            }


            $order = collect([])->pad(count($ids), 0)->map(fn($i, $k) => $k + 1)->toArray();
            $book->setSortableOrder($ids, $order);
        }
        return [
            '#books_list_partial' => $this->renderPartial('@default', ['books' => $this->getBooks()])
        ];
    }

    public function onUploadFile()
    {
        try {

            if (!Input::hasFile('fb2')) {
                throw new ValidationException(['fb2' => 'Файл отсутствует в запросе']);
            }
            $uploadedFile = Input::file('fb2');
            $validation = Validator::make(
                ['fb2' => $uploadedFile],
                ['fb2' => ['required', 'file', 'mimes:xml']]
            );
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            if (!$uploadedFile->isValid()) {
                throw new ValidationException(['fb2' => sprintf('File %s is not valid.', $uploadedFile->getClientOriginalName())]);
            }

            $book = (new FB2Manager())->apply($uploadedFile);

            return Redirect::to("/about-book/$book->id");
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

    public function onRefreshFiles()
    {
        $this->pageCycle();
    }
}
