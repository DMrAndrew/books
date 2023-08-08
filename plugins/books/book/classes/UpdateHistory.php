<?php

namespace Books\Book\Classes;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use October\Rain\Database\Collection;
use  Illuminate\Support\Collection as BaseCollection;

class UpdateHistory
{
    const CHUNK_LENGTH = 4999;

    protected BaseCollection $chunks;

    public function __construct(public Collection $collection)
    {
        $this->chunks = $this->makeChunks();
    }

    /**
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }

    /**
     * @return Collection
     */
    public function getChunks(): BaseCollection
    {
        return $this->chunks;
    }

    public function makeChunks(): BaseCollection
    {
        return $this->collection
            ->chunkWhile(fn($value, $key, $chunk) => (int)$chunk->sum('odds') <= self::CHUNK_LENGTH)
            ->filter(fn($i) => $i->sum('odds') >= self::CHUNK_LENGTH)
            ->map(fn($collection) => new UpdateHistoryItem(...[
                $collection->last()->created_at,
                (int)$collection->sum('odds'),
                (int)$collection->last()->new_value
            ]));
    }

    public function toView(): UpdateHistoryView
    {
        return new UpdateHistoryView($this);
    }
}

class UpdateHistoryView
{

    public int|float $freq;
    public string $freq_string;
    public BaseCollection $items;
    public int $count;
    public int $days;

    public function __construct(UpdateHistory $history)
    {
        $this->items = $history->getChunks()->reverse();
        $this->count = $history->getChunks()->count();
        $this->days = $this->count ? CarbonPeriod::create($this->items->last()->date, $this->items->first()->date)->count() : 0;
        $this->freq = $this->count ? $this->count / $this->days : 0;

        $this->freq_string = getFreqString($this->count, $this->days);
    }
}

class UpdateHistoryItem
{
    public function __construct(public Carbon $date, public int $value, public int $new_value)
    {
    }
}
