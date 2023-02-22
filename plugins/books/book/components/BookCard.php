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
        $user = Auth::getUser();
        $book = Book::find(post('book_id'));
        if ($user && $book) {
            $lib = $user->library($book);
            if(!$lib->has()){
                if ($lib->get()) {
                    $lib->interested();
                    $book->rater()->libs()->apply();
                }
            }
            else{
                $lib->remove();
            }
            $book->rater()->libs()->apply();
        }

        return $this->render(['book' => Book::query()->defaultEager()->find($book->id)]);
    }

    public function onLike()
    {
        $user = Auth::getUser();
        $book = Book::find(post('book_id'));
        if ($user && $book) {
            $user->toggleFavorite($book);
            $book->rater()->likes()->apply()
                ->rate()->queue();
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
