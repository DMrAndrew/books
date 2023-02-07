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

    public function parented(bool $child = false)
    {
        return Genre::query()->roots()->public()->when($child, fn($q) => $q->with('children'))->get();
    }

    public function allGenres()
    {
        return $this->parented(child: true)->split(4);
    }

}
