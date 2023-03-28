<?php

namespace Books\Book\Classes;

use BadMethodCallException;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Models\Book;
use Books\Book\Models\Stats;
use Books\Catalog\Classes\FavoritesManager;
use Books\Collections\classes\CollectionEnum;
use Books\Collections\Models\Lib;
use Cache;
use Carbon\Carbon;
use Cookie;
use Exception;
use October\Rain\Database\Builder;
use RainLab\User\Models\User;

class WidgetService
{
    protected bool $forceCache = false;

    protected string $cacheKey;

    protected string $cacheName = 'compilations';

    protected Builder $query;

    protected $values;

    /**
     * @throws Exception
     */
    public function __construct(protected WidgetEnum $enum,
                                protected ?User $user = null,
                                protected ?Carbon $cacheTTL = null,
                                protected Book $book = (new Book()),
                                protected bool $short = false,
                                protected bool $withHeader = true,
                                protected bool $disableCache = false,
                                protected bool $diffWithUser = false,
                                protected bool $withAll = false,
                                protected bool $useSort = true,
    ) {
        $this->cacheTTL ??= Carbon::now()->copy()->addMinutes(10);
        $this->values = collect();
        $this->cacheKey = $this->enum->value;
        if (in_array($this->enum, [WidgetEnum::readingWithThisOne, WidgetEnum::cycle, WidgetEnum::otherAuthorBook, WidgetEnum::popular])) {
            $this->validate();
            $this->cacheKey .= $this->book->id;
        }
        $this->query = Book::query()->onlyPublicStatus(); // Не использовать scope Public, который содержит scope adult, иначе в кэш не попадут 18+
    }

    /**
     * @param  bool  $useSort
     * @return WidgetService
     */
    public function setUseSort(bool $useSort): static
    {
        $this->useSort = $useSort;

        return $this;
    }

    /**
     * @param  bool  $withAll
     * @return WidgetService
     */
    public function setWithAll(bool $withAll): static
    {
        $this->withAll = $withAll;

        return $this;
    }

    /**
     * @param  bool  $diffWithUser
     * @return WidgetService
     */
    public function setDiffWithUser(bool $diffWithUser): static
    {
        $this->diffWithUser = $diffWithUser;

        return $this;
    }

    /**
     * @param  Builder  $query
     * @return WidgetService
     */
    public function setQuery(Builder $query): static
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @param  bool  $disableCache
     * @return WidgetService
     */
    public function setDisableCache(bool $disableCache): static
    {
        $this->disableCache = $disableCache;

        return $this;
    }

    /**
     * @param  bool  $forceCache
     * @return WidgetService
     */
    public function setForceCache(bool $forceCache): static
    {
        $this->forceCache = $forceCache;

        return $this;
    }

    /**
     * @param  Carbon  $cacheTTL
     * @return WidgetService
     */
    public function setCacheTTL(Carbon $cacheTTL): static
    {
        $this->cacheTTL = $cacheTTL;

        return $this;
    }

    /**
     * @param  User|null  $user
     * @return WidgetService
     */
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Carbon|null
     */
    public function getCacheTTL(): ?Carbon
    {
        return $this->cacheTTL;
    }

    /**
     * @return mixed
     */
    public function getValues()
    {
        return $this->values;
    }

    public static function clearCompilationsCache(): void
    {
        foreach ([WidgetEnum::hotNew, WidgetEnum::new, WidgetEnum::gainingPopularity] as $widget) {
            $widget->service()->clearCache();
        }
    }

    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }

    private function query()
    {
        return $this->query;
    }

    protected function validate()
    {
        if (! $this->book?->exists) {
            throw new Exception('Book required.');
        }
    }

    /**
     * @throws Exception
     */
    public function toWidgetParams(): array
    {
        return [
            'enum' => $this->enum,
            'items' => $this->apply()->getValues(),
            'short' => $this->short,
            'withHeader' => $this->withHeader,
            'withAll' => $this->withAll,
        ];
    }

    public function diffWithUser(): static
    {
        if (! $this->user || ! $this->diffWithUser || in_array($this->enum, [WidgetEnum::interested, WidgetEnum::cycle])) {
            return $this;
        }

        //diff
        return $this;
    }

    public function apply(): static
    {
        if ($this->disableCache || $this->forceCache || ! Cache::has($this->cacheKey)) {
            $this->collect();
            if ($this->enum === WidgetEnum::interested) {
                return $this;
            }
            if (! $this->disableCache) {
                $this->cache();
            }
            $ids = $this->values->pluck('id')->toArray();
        } else {
            $ids = Cache::get($this->cacheKey) ?? collect()->toArray();
        }
        $this->values = $this->query->public()->defaultEager()->whereIn('id', $ids)->get();

        return $this->diffWithUser()->sort();
    }

    public function cache(): static
    {
        $this->clearCache();
        Cache::remember($this->cacheKey, $this->cacheTTL, fn () => $this->values->pluck('id')->toArray());

        return $this;
    }

    public function collect(): static
    {
        $this->values = match ($this->enum) {
            WidgetEnum::hotNew, WidgetEnum::gainingPopularity => $this->getFor(),
            WidgetEnum::otherAuthorBook, WidgetEnum::readingWithThisOne,
            WidgetEnum::new, WidgetEnum::interested, WidgetEnum::popular, WidgetEnum::cycle, WidgetEnum::recommend => $this->{$this->enum->value}(),
            default => collect()
        };

        return $this;
    }

    public function sort(): static
    {
        if ($this->useSort) {
            $this->values = match ($this->enum) {
                WidgetEnum::hotNew, WidgetEnum::gainingPopularity => $this->values->sortByDesc(fn (Book $book) => $book->getCollectedRate($this->enum)),
                WidgetEnum::popular, WidgetEnum::recommend, WidgetEnum::otherAuthorBook => Book::sortCollectionByPopularGenre($this->values),
                WidgetEnum::new => $this->values->sortByDesc(fn ($b) => $b->ebook->sales_at),
                WidgetEnum::interested => $this->values->sortByDesc('created_at'),
                default => $this->values
            };
        }

        return $this;
    }

    private function getFor()
    {
        if (! method_exists((new Stats()), $this->enum->value)) {
            throw new BadMethodCallException();
        }

        return $this->query()
            ->when(in_array($this->enum, [WidgetEnum::hotNew, WidgetEnum::gainingPopularity]), fn ($q) => $q->afterPublishedAtDate(Carbon::now()
                ->copy()
                ->subDays($this->enum === WidgetEnum::hotNew ? 10 : 30)))
            ->get();
    }

    public function recommend()
    {
        return $this->query()->recommend()->get();
    }

    public function new()
    {
        return $this->query()->get();
    }

    public function interested()
    {
        $collection = $this->user?->getLib()[CollectionEnum::WATCHED->value] ?? collect();

        return $collection->pluck('book');
    }

    public function popular()
    {
        return $this->query()
            ->whereHas('genres', fn ($genres) => $genres->whereIn('id', $this->book->genres->pluck('id')->toArray()))
            ->whereNotIn('id', [$this->book->id])
            ->get();
    }

    public function otherAuthorBook()
    {
        return $this->query()
            ->whereNotIn('id', [$this->book->id])
            ->whereHas('author', fn ($author) => $author->where('profile_id', '=', $this->book->author->profile_id))
            ->whereHas('genres', fn ($genres) => $genres
                ->whereIn('id', ($this->user?->loved_genres
                    ?? json_decode(Cookie::get('loved_genres')) ?: (new FavoritesManager())->getDefaultGenres())))
            ->get();
    }

    public function readingWithThisOne()
    {
        $readingWithIds = Lib::query()
            ->where('type', '=', CollectionEnum::READ)
            ->with('favorites')
            ->get()
            ->each(fn ($i) => $i['user'] = $i->favorites->first()->user_id)->groupBy('user')
            ->map->pluck('book_id')
            ->filter(fn ($i) => ! is_bool($i->search($this->book->id)))
            ->flatten(1)
            ->groupBy(fn ($i) => $i)
            ->map->count()
            ->sortDesc()
            ->values()
            ->toArray();

        return $this->query()
            ->whereIn('id', $readingWithIds)
            ->hasGenres($this->book->genres()->pluck('id')->toArray())
            ->get();
    }

    public function cycle()
    {
        return $this->book->cycle?->books()->defaultEager()->get() ?? collect();
    }
}
