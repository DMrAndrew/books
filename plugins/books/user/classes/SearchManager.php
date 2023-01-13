<?php

namespace Books\User\Classes;

use Illuminate\Support\Collection;
use October\Rain\Database\Builder;
use October\Rain\Support\Collection as OctoberCollection;

class SearchManager
{
    protected array $searchable = [];

    public function __construct(protected string $query, protected ?string $needle)
    {
        $this->needle ??= 'books';
        $this->searchable = config('searchable') ?? [];
    }

    public function apply(): Collection|OctoberCollection
    {

        $queries = collect($this->searchable)
            ->map(fn($model) => $model::query()->searchByString($this->query));
        //TODO searchable behaviour

        return $queries
            ->filter(fn(Builder $query) => $query->exists())
            ->map(function (Builder $query, $name) {

                $count = $query->count();
                $rows = $name === $this->needle ? $query->get() : new \October\Rain\Database\Collection();
                $rows->load('user');

                return [
                    'name' => $name,
                    'count' => $count,
                    'rows' => $rows,
                    'label' => getCorrectSuffix($count, ($rows?->first() ?? $query->first())->endingArray),
                ];
            });
    }
}
