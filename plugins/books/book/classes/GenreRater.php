<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\StatsEnum;
use Books\Book\Models\Book;
use Books\Book\Models\Stats;
use Illuminate\Database\Eloquent\Builder;

class GenreRater
{
    protected Stats $stats;
    protected Book $book;
    protected Builder $builder;

    public function __construct()
    {

    }

    public function collectRate(): \October\Rain\Support\Collection|\Illuminate\Support\Collection
    {
        return collect([
            $this->stats[StatsEnum::RATE->value],
            min(15, $this->stats[StatsEnum::READ_TIME->value]) * 3,
            $this->stats[StatsEnum::COMMENTS->value],
            $this->stats[StatsEnum::READ_INITIAL->value] * 3,
            $this->stats[StatsEnum::READ->value],
            $this->stats[StatsEnum::READ_FINAL->value] * 3,
            $this->book->ebook->status === BookStatus::WORKING ? $this->stats[StatsEnum::UPDATE_FREQUENCY->value] * 3 : 0,
            $this->stats[StatsEnum::LIBS->value],
        ]);
    }

    public function getRate(): int
    {
        return $this->collectRate()->sum();
    }
}
