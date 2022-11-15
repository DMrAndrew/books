<?php namespace Books\Catalog\Components;

use Books\Catalog\Models\Genre;
use Cms\Classes\ComponentBase;

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

    public function parented(){
        return Genre::query()->parent()->active()->get();
    }
    public function favoriteGenres()
    {
        return Genre::query()->active()->favorites()->select(['id', 'name'])->get()->split(4);
    }

    public function allGenres()
    {
        $all = $this->parented();
        $all->load('children');
        return $all->split(4);
    }
}
