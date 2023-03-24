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
    protected bool $forceCache = true;

    protected string $cacheKey;

    protected Builder $query;

    public function __construct(protected WidgetEnum $enum,
                                protected ?User $user = null,
                                protected ?Carbon $cacheTTL = null,
                                protected Book $book = (new Book()),
                                protected bool $short = false,
                                protected bool $withHeader = true,
                                protected bool $disableCache = false,
                                protected bool $diffWithUser = false,
                                protected bool $withAll = false,
    ) {
        $this->cacheTTL ??= Carbon::now()->copy()->addHour();
        $this->cacheKey ??= $this->enum->value;
        $this->query = Book::query();
    }

    private function toArray(array $items): array
    {
        return [
            'enum' => $this->enum,
            'items' => $items,
            'short' => $this->short,
            'withHeader' => $this->withHeader,
            'withAll' => $this->withAll,
        ];
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

    public function get()
    {
        if ($this->disableCache || $this->forceCache || ! Cache::has($this->enum->value)) {
            $values = match ($this->enum) {
                WidgetEnum::hotNew, WidgetEnum::gainingPopularity => $this->getFor(),
                WidgetEnum::otherAuthorBook, WidgetEnum::readingWithThisOne,
                WidgetEnum::new, WidgetEnum::interested, WidgetEnum::popular, WidgetEnum::cycle, WidgetEnum::recommend => $this->{$this->enum->value}(),
                WidgetEnum::top => throw new Exception('To be implemented'),
                WidgetEnum::todayDiscount => throw new Exception('To be implemented'),
                WidgetEnum::bestsellers => throw new Exception('To be implemented'),
            };

            if ($this->disableCache) {
                return $this->diffWithUser($values);
            }
            $values = $values->map(function (Book $book) {
                $book['cover_thumb'] = $book->cover->getThumb(104, 150);

                return $book;
            })->toArray();

            if (in_array($this->enum, [WidgetEnum::interested, WidgetEnum::cycle])) {
                return $this->toArray($values);
            }

            Cache::forget($this->cacheKey);
            Cache::remember($this->cacheKey, $this->cacheTTL, fn () => $values);
        }

        return $this->toArray($this->diffWithUser(Cache::get($this->cacheKey)));
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
            ->get()
            ->sortByDesc(fn (Book $book) => $book->getCollectedRate($this->enum));
    }

    public function recommend()
    {
        return Book::sortCollectionByPopularGenre($this->query()->recommend()->get());

        return $this->query()->recommend()->get()->sortBy(fn ($book) => $book->genres->pluck('pivot')->min('rate_number') ?: 10000);
    }

    public function new()
    {
        return $this->query()->get()->sortByDesc(fn ($b) => $b->ebook->sales_at);
    }

    public function interested()
    {
        $collection = $this->user?->getLib()[CollectionEnum::WATCHED->value] ?? collect();

        return $collection?->pluck('book')?->sortByDesc('created_at') ?? collect();
    }

    public function popular()
    {
        $this->validate();

        return Book::sortCollectionByPopularGenre($this->query()
            ->whereHas('genres', fn ($genres) => $genres->whereIn('id', $this->book->genres->pluck('id')->toArray()))
            ->whereNotIn('id', [$this->book->id])
            ->get());

        return $this->query()
            ->whereHas('genres', fn ($genres) => $genres->whereIn('id', $this->book->genres->pluck('id')->toArray()))
            ->whereNotIn('id', [$this->book->id])
            ->get()
            ->sortBy(fn ($book) => $book->genres->pluck('pivot')->min('rate_number') ?: 10000);
    }

    public function otherAuthorBook()
    {
        $this->validate();

        return Book::sortCollectionByPopularGenre($this->query()
            ->whereNotIn('id', [$this->book->id])
            ->whereHas('author', fn ($author) => $author->where('profile_id', '=', $this->book->author->profile_id))
            ->whereHas('genres', fn ($genres) => $genres
                ->whereIn('id', ($this->user?->loved_genres
                    ?? json_decode(Cookie::get('loved_genres')) ?: (new FavoritesManager())->getDefaultGenres())))
            ->get());

        return $this->query()
            ->whereNotIn('id', [$this->book->id])
            ->whereHas('author', fn ($author) => $author->where('profile_id', '=', $this->book->author->profile_id))
            ->whereHas('genres', fn ($genres) => $genres
                ->whereIn('id', ($this->user?->loved_genres
                    ?? json_decode(Cookie::get('loved_genres')) ?: (new FavoritesManager())->getDefaultGenres())))
            ->get()
            ->sortBy(fn ($book) => $book->genres->pluck('pivot')->min('rate_number') ?: 10000);
    }

    protected function validate()
    {
        if (! $this->book?->exists) {
            throw new Exception('Book required.');
        }
    }

    public function readingWithThisOne()
    {
        $this->validate();
        $this->cacheKey = $this->enum->value.$this->book->id;
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
        $this->validate();
        $this->cacheKey = $this->enum->value.$this->book->id;

        return $this->book->cycle?->books()->defaultEager()->get() ?? collect();
    }

    private function query()
    {
        return $this->query->defaultEager()->public();
    }

    public function diffWithUser($collection)
    {
        if (! $this->user || ! $this->diffWithUser) {
            return $collection;
        }

        return $collection;
    }
}
