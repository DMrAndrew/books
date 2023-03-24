<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\StatsEnum;
use Books\Book\Models\Book;
use Books\Book\Models\Stats;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Conditionable;
use Queue;

class Rater
{
    use Conditionable;

    const MAX_READ_TIME_MINUTES_PER_PAGE = 15;

    const SHIFT_LENGTH = 5000;

    protected bool $withDump = false;

    protected Stats $stats;

    protected array $closures = [];

    protected Builder $builder;

    public function __construct(protected Book $book)
    {
        $this->stats = $this->book->exists ? $this->book->stats : new Stats();
        $this->builder = Book::query();
    }

    /**
     * @param  bool  $withDump
     */
    public function setWithDump(bool $withDump): static
    {
        $this->withDump = $withDump;

        return $this;
    }

    private function canPerform(): bool
    {
        return $this->stats->exists && count($this->closures);
    }

    private function set(string $stat_key, ?string $book_key = null, mixed $value = null): void
    {
        $this->stats->fill([$stat_key => $value ?? $this->book[$book_key ?? $stat_key]]);
    }

    /**
     * @return Stats
     */
    public function getStats(): Stats
    {
        return $this->stats;
    }

    public function performClosures(): static
    {
        if ($this->canPerform()) {
            $this->book = $this->builder->find($this->book->id);

            foreach ($this->closures as $closure) {
                $closure();
            }
        }

        return $this;
    }

    /**
     * @return Builder
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    public function apply(): static
    {
        $this->performClosures();
        $this->stats->save();
        $this->closures = [];

        if ($this->withDump) {
            $this->stats->dump();
        }

        return $this;
    }

    public function queue(): ?static
    {
        if (! $this->canPerform()) {
            return null;
        }
        $actions = array_keys($this->closures);
        $id = $this->book->id;
        Queue::push(function ($job) use ($id, $actions) {
            try {
                $r = new static(Book::find($id));
                foreach ($actions as $action) {
                    $r->{$action}();
                }
                $r->apply();
                $job->delete();
            } catch (Exception $exception) {
                //
            }
        });

        return $this;
    }

    public function applyStats(StatsEnum ...$stats): static
    {
        foreach ($stats as $stat) {
            $this->{$stat->value}();
        }

        return $this;
    }

    public function applyStatsAll(): static
    {
        $this->applyStats(
            StatsEnum::LIBS,
            StatsEnum::COMMENTS,
            StatsEnum::READ,
            StatsEnum::LIKES,
            StatsEnum::UPDATE_FREQUENCY,
            StatsEnum::READ_TIME
        );

        return $this;
    }

    public function applyAllBook(StatsEnum ...$stats): int
    {
        $count = 0;
        foreach (Book::cursor() as $book) {
            $book->rater()
                ->setWithDump($this->withDump)
                ->when(count($stats),
                    fn ($rater) => $rater->applyStats($stats),
                    fn ($rater) => $rater->applyStatsAll())
                ->apply();
            $count++;
        }

        return $count;
    }

    public function rate(): static
    {
        $this->likes();
        $this->closures[StatsEnum::RATE->value] = function () {
            $this->book['rate'] = $this->book['likes_count']; // Пока есть только лайки
            $this->set('rate');
        };

        return $this;
    }

    public function frequency(): static
    {
        $this->closures[StatsEnum::UPDATE_FREQUENCY->value] = function () {
            $this->book['freq'] = $this->book->ebook->updateHistory['freq'];
            $this->set('freq');
        };

        return $this;
    }

    public function time(): static
    {
        $this->closures[StatsEnum::READ_TIME->value] = function () {
            $this->book['read_time'] = (int) ceil($this->book
                    ->paginationTrackers()
                    ->where('time', '>', 0)
                    ->get()
                    ->where('time', '<', self::MAX_READ_TIME_MINUTES_PER_PAGE * 60)
                    ->sum('time') / 60);
            $this->set('read_time');
        };

        return $this;
    }

    public function read(): static
    {
        $this->closures[StatsEnum::READ->value] = function () {
            $this->book['read_count'] = $this->book->ebook->chapters()->withReadTrackersCount()->get()->sum('completed_trackers');
            $this->set('read_count');
        };

        return $this;
    }

    public function likes(): static
    {
        $this->builder->likesCount();
        $this->closures[StatsEnum::LIKES->value] = fn () => $this->set('likes_count');

        return $this;
    }

    public function libs(): static
    {
        $this->builder->inLibCount();
        $this->closures[StatsEnum::LIBS->value] = fn () => $this->set('in_lib_count');

        return $this;
    }

    public function comments(): static
    {
        $this->builder->commentsCount($this->book->profile);
        $this->closures[StatsEnum::COMMENTS->value] = fn () => $this->set('comments_count');

        return $this;
    }
}
