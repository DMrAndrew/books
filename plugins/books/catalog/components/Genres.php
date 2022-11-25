<?php namespace Books\Catalog\Components;

use Cms\Classes\ComponentBase;
use Books\Catalog\Models\Genre;

/**
 * Genres Component
 */
class Genres extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name' => 'Genres Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function parented()
    {
        return Genre::query()->roots()->active()->get();
    }

    public function allGenres()
    {
        $all = $this->parented();
        $all->load('children');
        return $all->split(4);
    }

}
