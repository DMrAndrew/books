<?php

namespace Books\Catalog\Components;

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
            'description' => 'No description provided yet...',
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function parented()
    {
        return $this->query()->roots()->get();
    }

    protected function query(bool $child = false)
    {
        return Genre::query()->public()->when($child, fn ($q) => $q->with('children'));
    }

    public function allGenres()
    {
        $genres = $this->query(child: true)->with(['books' => fn ($books) => $books->public()->select(['id', 'title'])])->getNested();
        $genres->each(function ($genre) {
            $genre['books_count'] = $genre->children->pluck('books')->flatten(1)->concat($genre->books)->unique(fn ($i) => $i->id)->count() ?: '';
        });

        return $genres;
    }
}
