<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\EditionsEnums;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use October\Rain\Database\Collection;
use Illuminate\Support\Collection as BaseCollection;

class UpdateHistory
{
    const HISTORY_UPDATE_VALUEABLE_SIZE_EBOOK = 4999; // ebook кол-во символов
    const HISTORY_UPDATE_VALUEABLE_SIZE_AUDIO = 3 * 60; // audiobook кол-во секунд

    protected BaseCollection $chunks;

    public function __construct(public Collection $collection, EditionsEnums $editionType = EditionsEnums::Ebook)
    {
        $this->chunks = $this->makeChunks($editionType);
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

    /**
     * @param EditionsEnums $editionType
     *
     * @return BaseCollection
     */
    public function makeChunks(EditionsEnums $editionType): BaseCollection
    {
        $chunkSize = match ($editionType) {
            EditionsEnums::Ebook => self::HISTORY_UPDATE_VALUEABLE_SIZE_EBOOK,
            EditionsEnums::Audio => self::HISTORY_UPDATE_VALUEABLE_SIZE_AUDIO,
        };

        return $this->collection
            ->chunkWhile(fn($value, $key, $chunk) => (int)$chunk->sum('odds') <= $chunkSize)
            ->filter(fn($i) => $i->sum('odds') >= $chunkSize)
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
