<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\StatsEnum;
use Books\Book\Jobs\RaterExec;
use Books\Book\Models\Book;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Conditionable;
use October\Rain\Database\Collection;

/**
 * Класс Rater предназначен для рейтинговой системы книг.
 *
 * @uses Conditionable
 */
class Rater
{
    use Conditionable;

    protected Collection|array|\Illuminate\Database\Eloquent\Collection|null $result = null;

    protected array $closures = [];
    protected ?Builder $builder = null;

    /**
     * Создает новый экземпляр класса Rater.
     *
     * @param Book|null $book Объект книги.
     * @param int|null $ofLastDays Длительность в днях.
     * @param bool $withDump Флаг для определения, включать ли дамп.
     */
    public function __construct(protected ?Book $book = null,
                                protected ?int  $ofLastDays = null,
                                protected bool  $withDump = false)
    {
        $this->setBuilder(Book::query());
    }

    /**
     * @param int|null $ofLastDays
     * @return Rater
     */
    public
    function setOfLastDays(?int $ofLastDays): static
    {
        $this->ofLastDays = is_int($ofLastDays) ? abs($ofLastDays) : $ofLastDays;
        return $this;
    }


    /**
     * @param Builder $builder
     * @return Rater
     */
    public
    function setBuilder(Builder $builder): static
    {
        $this->builder = $builder->with('stats')
            ->when($this->book?->exists, fn($b) => $b->where(Book::make()->getQualifiedKeyName(), $this->book->id));

        return $this;
    }

    public
    function getResult(): \Illuminate\Database\Eloquent\Collection|Collection|array|null
    {
        return $this->result;
    }

    /**
     * @return int|null
     */
    public
    function getOfLastDays(): ?int
    {
        return $this->ofLastDays;
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

    /**
     */
    public function run(): static
    {
        $this->performClosures();

        $this->result->map->stats->each->save();
        $this->closures = [];

        return $this;
    }

    public
    function queue(): static
    {
        if ($this->canPerform()) {
            RaterExec::dispatch($this->toArray());
        }
        return $this;
    }

    public function toArray(): array
    {
        return [
            'ids' => $this->builder?->select('id')->get()->pluck('id')->toArray() ?? [],
            'closures' => array_keys($this->closures),
            'withDump' => $this->withDump,
            'ofLastDays' => $this->ofLastDays,
        ];
    }

    public static function make(array $payload): static
    {
        $r = new static();
        $r->setBuilder($r->getBuilder()->when($payload['ids'] ?? false, fn($b) => $b->whereIn('id', $payload['ids'])));
        $r->setWithDump($payload['withDump'] ?? false);
        $r->setOfLastDays($payload['ofLastDays'] ?? false);
        $r->applyStats(...collect($payload['closures'] ?? [])->map(fn($i) => StatsEnum::tryFrom($i))->filter());

        return $r;

    }


    public function applyStats(StatsEnum ...$stats): static
    {
        foreach ($stats as $stat) {
            $this->applyScope($stat);
            $this->applyClosure($stat);
        }

        return $this;
    }

    public
    function applyAllStats(): static
    {
        $this->applyStats(...StatsEnum::cases());

        return $this;
    }

    protected function applyScope(StatsEnum $stat): static
    {
        match ($stat) {
            StatsEnum::LIKES => $this->builder->likesCount(fn($b) => $this->applyOfLastDaysScope($b)),
            StatsEnum::LIBS => $this->builder->inLibCount(fn($b) => $this->applyOfLastDaysScope($b)),
            StatsEnum::COMMENTS => $this->builder->withCount(['comments' => fn($comments) => $this->applyOfLastDaysScope($comments)]),
            StatsEnum::READ => $this->builder->withReadChaptersTrackersCount(fn($b) => $this->applyOfLastDaysScope($b)),
            StatsEnum::RATE => $this->applyStats(StatsEnum::LIKES)
                && $this->builder->withSum('awardsItems', 'rate')->withCount('reposts'),
            StatsEnum::READ_TIME => $this->builder->withReadTime(fn($b) => $this->applyOfLastDaysScope($b)),
            StatsEnum::sells_count => $this->builder->withCountEditionSells(fn($b) => $this->applyOfLastDaysScope($b)),
            default => null
        };

        return $this;
    }

    protected function applyClosure(StatsEnum $stat): static
    {
        $this->closures[$stat->value] = fn(Book $book) => match ($stat) {
            StatsEnum::LIKES => $book->stats->likes_count = $book->likes_count,
            StatsEnum::LIBS => $book->stats->in_lib_count = $book->in_lib_count,
            StatsEnum::COMMENTS => $book->stats->comments_count = $book->comments_count,
            StatsEnum::READ => $book->stats->read_count = $book->chapters_trackers_count,
            StatsEnum::RATE => $book->stats->rate = collect([
                $book['likes_count'] ?? 0,
                $book['awards_items_sum_rate'] ?? 0,
                ($book['reposts_count'] ?? 0) * 2,
            ])->sum(),
            StatsEnum::READ_TIME => $book->stats->read_time = (int)ceil((int)$book->pagination_trackers_sum_time / 60),
            StatsEnum::UPDATE_FREQUENCY => $book->stats->freq = $book->ebook->getUpdateHistoryViewAttribute()->freq,
            StatsEnum::COLLECTED_GENRE_RATE => $book->stats->collected_genre_rate = $book->stats->forGenres($book->isWorking()),
            StatsEnum::collected_gain_popularity_rate => $book->stats->collected_gain_popularity_rate = $book->stats->gainingPopularity($book->isWorking()),
            StatsEnum::collected_hot_new_rate => $book->stats->collected_hot_new_rate = $book->stats->hotNew($book->isWorking()),
            StatsEnum::sells_count => $book->stats->sells_count = $book->sells_count,
            default => $book
        };
        return $this;
    }

    protected function applyOfLastDaysScope(Builder $builder)
    {
        return $builder->when($this->ofLastDays, fn($q) => $q->ofLastDays($this->ofLastDays));
    }

    /**
     * @throws Exception
     */
    public function __call(string $name, array $arguments)
    {
        if (in_array($name, StatsEnum::toArray())) {
            return $this->applyStats(StatsEnum::tryFrom($name), ...$arguments);
        } else {

            throw new Exception(sprintf('%s: метод %s не найден.', __CLASS__, $name));
        }
    }


}
