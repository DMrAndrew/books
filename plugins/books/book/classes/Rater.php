<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\StatsEnum;
use Books\Book\Jobs\RaterExec;
use Books\Book\Models\Book;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Conditionable;

class Rater
{
    use Conditionable;

    const MAX_READ_TIME_MINUTES_PER_PAGE = 15;


    protected $result = null;

    protected array $closures = [];
    protected ?Builder $builder = null;

    public function __construct(protected ?Book $book = null,
                                protected ?int  $dateBetween = null,
                                protected bool  $withDump = false)
    {
        $this->setBuilder(Book::query());
    }


    /**
     * @param int|null $dateBetween
     * @return Rater
     */
    public
    function setDateBetween(?int $dateBetween): static
    {
        $this->dateBetween = is_int($dateBetween) ? abs($dateBetween) : $dateBetween;
        return $this;
    }


    /**
     * @param Builder $builder
     */
    public
    function setBuilder(Builder $builder): static
    {
        $this->builder = $builder
            ->with('stats')
            ->when($this->book?->exists, fn($b) => $b->where('id', $this->book->id));

        return $this;
    }

    public
    function getResult()
    {
        return $this->result;
    }

    /**
     * @return int|null
     */
    public
    function getDateBetween(): ?int
    {
        return $this->dateBetween;
    }

    /**
     * @param bool $withDump
     * @return Rater
     */
    public
    function setWithDump(bool $withDump): static
    {
        $this->withDump = $withDump;

        return $this;
    }


    private
    function canPerform(): bool
    {
        return count($this->closures);
    }

    public
    function performClosures(): static
    {
        if ($this->canPerform()) {
            $this->result = $this->builder->get();
            foreach ($this->result as $book) {
                foreach ($this->closures as $closure) {
                    $closure($book);
                }
                if ($this->withDump) {
                    $book->stats->dump();
                }
            }
        }

        return $this;
    }

    /**
     * @return Builder
     */
    public
    function getBuilder(): Builder
    {
        return $this->builder;
    }

    public
    function apply(): static
    {
        $this->performClosures();
        $this->result->map->stats->each->save();
        $this->closures = [];

        return $this;
    }

    public
    function queue(): ?static
    {
        if (!$this->canPerform()) {
            return null;
        }

        $data = [
            'ids' => $this->builder->select('id')->get()->pluck('id'),
            'closures' => array_keys($this->closures),
            'withDump' => $this->withDump,
            'dateBetween' => $this->dateBetween,
        ];
        RaterExec::dispatch($data);
        return $this;
    }


    function applyStats(StatsEnum ...$stats): static
    {
        foreach ($stats as $stat) {
            $this->{$stat->value}();
        }

        return $this;
    }

    public
    function applyAllStats(): static
    {
        $this->applyStats(
            StatsEnum::LIBS,
            StatsEnum::COMMENTS,
            StatsEnum::READ,
            StatsEnum::LIKES,
            StatsEnum::UPDATE_FREQUENCY,
            StatsEnum::READ_TIME,
            StatsEnum::collected_gain_popularity_rate,
            StatsEnum::collected_hot_new_rate,
            StatsEnum::sells_count,
        );

        return $this;
    }

    public
    function rate(): static
    {
        $this->likes();
        $this->builder->withSum('awardsItems', 'rate');
        $this->builder->withCount('reposts');
        $this->closures[StatsEnum::RATE->value] = function (Book $book) {
            $rate = collect([
                $book['likes_count'] ?? 0,
                $book['awards_items_sum_rate'] ?? 0,
                ($book['reposts_count'] ?? 0) * 2,

            ])->sum();
            $book->stats->rate = $rate;

        };

        return $this;
    }

    public
    function frequency(): static
    {
        $this->closures[StatsEnum::UPDATE_FREQUENCY->value] = function (Book $book) {
            $book->stats->freq = $book->ebook->updateHistory['freq'];
        };

        return $this;
    }

    public
    function time(): static
    {
        $this->builder->withReadTime(ofLastDays: $this->dateBetween, maxTime: Rater::MAX_READ_TIME_MINUTES_PER_PAGE * 60);
        $this->closures[StatsEnum::READ_TIME->value] = function (Book $book) {
            $book->stats->read_time = (int)ceil($book->pagination_trackers_sum_time / 60);
        };

        return $this;
    }

    public
    function read(): static
    {
        $this->builder->withReadChaptersTrackersCount();
        $this->closures[StatsEnum::READ->value] = function (Book $book) {
            $book->stats->read_count = $book->chapters_trackers_count;
        };

        return $this;
    }

    public
    function likes(): static
    {
        $this->builder->likesCount($this->dateBetween);
        $this->closures[StatsEnum::LIKES->value] = fn(Book $book) => $book->stats->likes_count = $book->likes_count;

        return $this;
    }

    public
    function libs(): static
    {
        $this->builder->inLibCount($this->dateBetween);
        $this->closures[StatsEnum::LIBS->value] = fn(Book $book) => $book->stats->in_lib_count = $book->in_lib_count;

        return $this;
    }

    public
    function comments(): static
    {
        $this->builder->commentsCount(withOutAuthorProfile: true, ofLastDays: $this->dateBetween);
        $this->closures[StatsEnum::COMMENTS->value] = fn(Book $book) => $book->stats->comments_count = $book->comments_count;

        return $this;
    }

    public
    function collected_genre_rate(): static
    {
        $this->closures[StatsEnum::COLLECTED_GENRE_RATE->value] = fn(Book $book) => $book->stats->collected_genre_rate = $book->stats->forGenres($book->isWorking());
        return $this;
    }

    public
    function collected_gain_popularity_rate(): static
    {
        $this->closures[StatsEnum::collected_gain_popularity_rate->value] = fn(Book $book) => $book->stats->collected_genre_rate = $book->stats->gainingPopularity($book->isWorking());
        return $this;
    }

    public
    function collected_hot_new_rate(): static
    {
        $this->closures[StatsEnum::collected_hot_new_rate->value] = fn(Book $book) => $book->stats->collected_hot_new_rate = $book->stats->hotNew($book->isWorking());
        return $this;
    }

    public
    function sells_count(): static
    {
        $this->builder->withCountEditionSells($this->dateBetween);
        $this->closures[StatsEnum::sells_count->value] = fn(Book $book) => $book->stats->sells_count = $book->customers_count;
        return $this;
    }


}
