<?php

namespace Books\Book\Classes;

use BadMethodCallException;
use Books\Book\Classes\Enums\StatsEnum;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Models\Author;
use Books\Book\Models\Book;
use Books\Book\Models\Stats;
use Books\Book\Models\Tag;
use Books\Catalog\Classes\FavoritesManager;
use Books\Catalog\Models\Genre;
use Books\Collections\classes\CollectionEnum;
use Books\Collections\Models\Lib;
use Cache;
use Carbon\Carbon;
use Cookie;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use October\Rain\Database\Builder;
use RainLab\User\Models\User;

class WidgetService
{
    protected bool $forceCache = false;

    protected string $cacheKey;

    protected Builder $builder;

    protected $values;

    protected int $limit = 10;

    /**
     * @throws Exception
     */
    public function __construct(protected WidgetEnum $enum,
                                protected ?User      $user = null,
                                protected ?Carbon    $cacheTTL = null,
                                protected Book       $book = (new Book()),
                                protected bool       $short = false,
                                protected bool       $withHeader = true,
                                protected bool       $disableCache = false,
                                protected bool       $diffWithUser = true,
                                protected bool       $withAll = false,
                                protected bool       $useSort = true,
    )
    {
        $this->cacheTTL ??= Carbon::now()->copy()->addMinutes(10);
        $this->values = collect();
        $this->cacheKey = $this->enum->value;
        if (in_array($this->enum, [WidgetEnum::readingWithThisOne, WidgetEnum::cycle, WidgetEnum::otherAuthorBook, WidgetEnum::popular])) {
            $this->validate();
            $this->cacheKey .= $this->book->id;
        }
        $this->builder = $this->clearQuery();
        $this->setShort($this->short);
    }


    public function clearQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Book::query()->onlyPublicStatus();// Не использовать scope Public, который содержит scope adult, иначе в кэш не попадут 18+
    }

    /**
     * @param bool $short
     */
    public function setShort(bool $short): void
    {
        $this->short = $short;
        $this->limit = $this->short ? 3 : 10;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @param bool $useSort
     * @return WidgetService
     */
    public function setUseSort(bool $useSort): static
    {
        $this->useSort = $useSort;

        return $this;
    }

    /**
     * @param bool $withAll
     * @return WidgetService
     */
    public function setWithAll(bool $withAll): static
    {
        $this->withAll = $withAll;

        return $this;
    }

    /**
     * @param bool $diffWithUser
     * @return WidgetService
     */
    public function setDiffWithUser(bool $diffWithUser): static
    {
        $this->diffWithUser = $diffWithUser;

        return $this;
    }

    /**
     * @param Builder $builder
     * @return WidgetService
     */
    public function setBuilder(Builder $builder): static
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * @param bool $disableCache
     * @return WidgetService
     */
    public function setDisableCache(bool $disableCache): static
    {
        $this->disableCache = $disableCache;

        return $this;
    }

    /**
     * @param bool $forceCache
     * @return WidgetService
     */
    public function setForceCache(bool $forceCache): static
    {
        $this->forceCache = $forceCache;

        return $this;
    }

    /**
     * @param Carbon $cacheTTL
     * @return WidgetService
     */
    public function setCacheTTL(Carbon $cacheTTL): static
    {
        $this->cacheTTL = $cacheTTL;

        return $this;
    }

    /**
     * @param User|null $user
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
    public function getValues(): mixed
    {
        return $this->values;
    }

    public static function clearCompiledCache(): void
    {
        foreach ([WidgetEnum::hotNew, WidgetEnum::new, WidgetEnum::gainingPopularity, WidgetEnum::bestsellers, WidgetEnum::top] as $widget) {
            $widget->service()->clearCache();
        }
    }

    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }

    private function query()
    {
        return $this->builder;
    }

    /**
     * @return Builder
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * @throws Exception
     */
    protected function validate(): void
    {
        if (!$this->book?->exists) {
            throw new Exception('Book required.');
        }
    }

    public function cache(): static
    {
        $this->clearCache();
        Cache::remember($this->cacheKey, $this->cacheTTL, fn() => $this->values->pluck('id')->toArray());

        return $this;
    }

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

    public function apply(): static
    {
        if ($this->disableCache || $this->forceCache || !Cache::has($this->cacheKey)) {
            $this->collect();
            if (in_array($this->enum, [WidgetEnum::interested, WidgetEnum::cycle])) {
                return $this;
            }
            if (!$this->disableCache) {
                $this->cache();
            }
            $ids = $this->values->pluck('id');
        } else {
            $ids = Cache::get($this->cacheKey) ?? collect()->toArray();
        }
        $this->applySort();
        $this->values = $this->query()
            ->public()
            ->defaultEager()
            ->whereIn((new Book())->getQualifiedKeyName(), $ids)
            ->when($this->diffWithUser, function ($builder) {
                $builder->whereNotIn((new Book())->getQualifiedKeyName(), $this->user?->queryLibs()
                    ->with('favorable')
                    ->get()
                    ->pluck('favorable')
                    ->pluck('book_id')
                    ->toArray() ?? [])
                    ->diffWithUnloved();
            })
            ->limit($this->limit)
            ->get();

        return $this;
    }

    public function applyEnum()
    {
        $this->applySort();
        return method_exists($this, $this->enum->value) ? $this->{$this->enum->value}() : $this->emptyBuilder();
    }

    public function collect(): static
    {
        $this->values = $this->applyEnum();

        return $this;
    }

    public function applySort(): static
    {
        $this->query()->when($this->useSort, fn($q) => match ($this->enum) {
            WidgetEnum::cycle, WidgetEnum::readingWithThisOne, WidgetEnum::interested => $q,
            WidgetEnum::hotNew => $q->sortByStatValue(StatsEnum::collected_hot_new_rate),
            WidgetEnum::popular, WidgetEnum::otherAuthorBook, WidgetEnum::recommend => $q->orderByPopularGenres(),
            WidgetEnum::new => $q->orderBySalesAt(),
            WidgetEnum::top, WidgetEnum::bestsellers => $q->orderByBestSells(),
            WidgetEnum::todayDiscount => $q->orderByDiscountAmount(),
            WidgetEnum::gainingPopularity => $q->sortByStatValue(StatsEnum::collected_gain_popularity_rate),
        });

        return $this;
    }

    public function top()
    {
        $ids = Author::query()
            ->owner()
            ->orderByLeftPowerJoinsCount('book.editions.customers.id', 'desc')
            ->get()
            ->where('customers_aggregation', '>', 0)
            ->unique('profile_id')
            ->pluck('book_id')
            ->toArray();

        return $this->query()->whereIn((new Book())->getQualifiedKeyName(), $ids);
    }

    public function bestsellers()
    {
        return $this->query()->customersExists();
    }

    public function hotNew()
    {
        return $this->query()->afterPublishedAtDate(date: 10);
    }

    public function gainingPopularity()
    {
        return $this->query()->afterPublishedAtDate(date: 30);
    }

    public function todayDiscount()
    {
        return $this->query()->notFree()->activeDiscountExist();
    }

    public function recommend()
    {
        return $this->query()->recommend();
    }

    public function new()
    {
        return $this->query();
    }

    public function interested()
    {
        $collection = $this->user?->getLib()[CollectionEnum::WATCHED->value] ?? collect();
        $inlib = $collection->pluck('book')->pluck('id')->toArray();

        return $this->query()->whereIn('id', $inlib);
    }

    public function popular()
    {
        $genres = $this->book->genres()->pluck('id')->toArray();
        return $this->query()
            ->hasGenres($genres)
            ->whereNot((new Book())->getQualifiedKeyName(), $this->book->id);
    }

    public function otherAuthorBook()
    {
        return $this->query()
            ->whereNotIn('id', [$this->book->id])
            ->whereHas('author', fn($author) => $author->where('profile_id', '=', $this->book->author()->value('profile_id')))
            ->whereHas('genres', fn($genres) => $genres
                ->whereIn('id', $this->user?->loved_genres ?? getUnlovedFromCookie()));
    }

    public function readingWithThisOne()
    {
        $readingWithIds = Lib::query()
            ->type(CollectionEnum::READ)
            ->with('favorites')
            ->get()
            ->each(fn($i) => $i['user'] = $i->favorites?->first()->user_id)->groupBy('user')
            ->map->pluck('book_id')
            ->filter(fn($i) => !is_bool($i->search($this->book->id)))
            ->flatten(1)
            ->groupBy(fn($i) => $i)
            ->map->count()
            ->sortDesc()
            ->values()
            ->toArray();


        $ids = $this->book->genres()->pluck('id')->toArray();
        return $this->query()
            ->whereIn((new Book())->getQualifiedKeyName(), $readingWithIds)
            ->hasGenres($ids);
    }

    public function cycle()
    {
        //todo REF
        return $this->book->cycle?->books()->public()->defaultEager() ?? $this->emptyBuilder();
    }

    public function emptyBuilder()
    {
        return $this->query()->where('id', null);
    }
}
