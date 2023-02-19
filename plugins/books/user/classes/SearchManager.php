<?php

namespace Books\User\Classes;

use Books\Book\Models\Book;
use Books\Profile\Models\Profile;
use ProtoneMedia\LaravelCrossEloquentSearch\Search;

class SearchManager
{
    protected int $active = 0;

    protected array $classes = [
        'Book' => Book::class,
        'Profile' => Profile::class,
    ];

    public function apply(string $query): \October\Rain\Support\Collection|\Illuminate\Support\Collection
    {
        $res = Search::add(Book::public()->defaultEager(), 'title')
            ->add(Profile::query()->bookExists()->booksCount()->with(['avatar']), 'username')
            ->includeModelType()
            ->orderByModel([
                Book::class, Profile::class])
            ->search($query)
            ->groupBy('type');

        return $res->map(function ($grouped, $key) {
            $class = $this->classes[$key];
            $count = $grouped->count();

            return [
                'active' => ! (bool) $this->active++,
                'count' => $count,
                'label' => $class::wordForm()->getCorrectSuffix($count),
                'items' => $grouped,
                'type' => $key,
            ];
        });
    }
}
