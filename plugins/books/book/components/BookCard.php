<?php namespace Books\Book\Components;

use Books\Book\Models\Book;
use Books\Collections\classes\CollectionEnum;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * BookCard Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BookCard extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'BookCard Component',
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

    public function onAddLib()
    {
        $id = post('book_id');
        $user = Auth::getUser();
        $book = Book::find($id);
        if ($user && $book) {
            $lib = $user->library($book);
            if ($lib->get()->type === CollectionEnum::WATCHED) {
                $lib->interested();
            }
        }

        return $this->render(['book' => Book::query()->defaultEager()->find($book->id)]);
    }

    public function onLike()
    {
        $id = post('book_id');
        $user = Auth::getUser();
        $book = Book::find($id);
        if ($user && $book) {
            $user->toggleFavorite($book);
        }

        return $this->render(['book' => Book::query()->defaultEager()->find($book->id)]);
    }

    public function render(array $options = [])
    {
        if ($partial = request()->header('X-OCTOBER-REQUEST-PARTIAL')) {
            return [
                $partial => $this->renderPartial($partial, $options)
            ];
        }

    }


}
