<?php

namespace Books\Book\Classes;

use Queue;
use Exception;
use Books\Book\Models\Book;
use Books\Book\Models\Stats;
use Books\Book\Classes\Enums\StatsEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Conditionable;

class Rater
{
    use Conditionable;

    protected Stats $stats;
    protected array $closures = [];
    protected Builder $builder;

    public function __construct(protected Book $book)
    {
        $this->stats = $this->book->exists ? $this->book->stats : new Stats();
        $this->builder = Book::query();
    }

    private function scopeOption(string $stat): ?string
    {
        return match (StatsEnum::tryFrom($stat)) {
            StatsEnum::LIKES => 'likesCount',
            StatsEnum::LIBS => 'inLibCount',
            StatsEnum::COMMENTS => 'commentsCount',
            default => null
        };
    }

    private function applyScopes(): void
    {
        foreach (array_keys($this->closures) as $closure) {
            if ($scope = $this->scopeOption($closure)) {
                $this->builder->{$scope}();
            }
        }
    }

    private function canPerform(): bool
    {
        return $this->stats->exists && count($this->closures);
    }

    private function set(string $stat_key, ?string $book_key = null, mixed $value = null): void
    {
        $this->stats->fill([$stat_key => $value ?? $this->book[$book_key ?? $stat_key]]);
    }

    public function apply(): static
    {
        if ($this->canPerform()) {

            $this->applyScopes();

            $this->book = $this->builder->find($this->book->id);

            foreach ($this->closures as $closure) {
                $closure();
            }
            $this->stats->save();
        }
        $this->closures = [];
        return $this;
    }

    public function queue(): ?static
    {
        if (!$this->canPerform()) {
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
        $this->applyStats(...StatsEnum::cases());
        return $this;
    }

    public static function queueAllBook(StatsEnum ...$stats): int
    {
        $count = 0;
        foreach (Book::cursor() as $book) {
            $book->rater()
                ->when(!count($stats),
                    fn($rater) => $rater->applyStatsAll(),
                    fn($rater) => $rater->applyStats($stats))
                ->queue();
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

    public function read(): static
    {
        $this->closures[StatsEnum::READ->value] = function () {
            $this->book['read_count'] = $this->book->ebook->chapters()->withReadTrackersCount()->get()
                ->sum('completed_trackers');
            $this->set('read_count');
        };
        return $this;
    }

    public function likes(): static
    {
        $this->closures[StatsEnum::LIKES->value] = fn() => $this->set('likes_count');

        return $this;
    }

    public function libs(): static
    {
        $this->closures[StatsEnum::LIBS->value] = fn() => $this->set('in_lib_count');

        return $this;
    }

    public function comments(): static
    {
        $this->closures[StatsEnum::COMMENTS->value] = fn() => $this->set('comments_count');

        return $this;
    }


}
