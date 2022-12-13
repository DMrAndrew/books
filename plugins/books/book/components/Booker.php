<?php namespace Books\Book\Components;

use Books\Book\Models\Book;
use Books\Catalog\Models\Genre;
use Cms\Classes\ComponentBase;
use October\Rain\Database\Traits\DeferredBinding;

/**
 * Booker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Booker extends ComponentBase
{


    protected Book $book;


    public function init()
    {
        $this->book = new Book();
    }


    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'Booker Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function onAddGenre(): array
    {

        if ($genre = Genre::find(post('id'))) {
            $this->book->bindDeferred('genres', $genre, $this->getSessionKey());
        }

        return $this->generateGenresInput();

    }

    public function onDeleteGenre(): array
    {
        if ($genre = Genre::find(post('id'))) {
            $this->book->unbindDeferred('genres', $genre, $this->getSessionKey());
        }

        return $this->generateGenresInput();
    }

    protected function generateGenresInput(): array
    {
        return [
            '#genresInput' => $this->renderPartial('@genresInput',
                ['genres' =>
                    $this->book->genres()
                        ->withDeferred($this->getSessionKey())
                        ->get()])
        ];
    }

    public function onSearchGenre()
    {

        $name = post('searchgenre');
        if ($name && strlen($name) > 2) {
            $array = Genre::active()
                ->child()
                ->name($name)
                ->exclude($this->book->genres()
                    ->withDeferred($this->getSessionKey())
                    ->get())
                ->get()
                ->toArray();
        } else {
            $array = [];
        }
        return [
            '#genresInput' => $this->renderPartial('@genresInput',
                ['genres' =>
                    $this->book->genres()
                        ->withDeferred($this->getSessionKey())
                        ->get(),
                    'search' => $array,
                    'searchgenrestring' => $name
                ])

        ];
    }

    /**
     * getSessionKey
     */
    public function getSessionKey()
    {
        return post('_session_key', $this->sessionKey);
    }

}
