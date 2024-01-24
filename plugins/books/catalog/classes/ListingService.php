<?php

namespace Books\Catalog\Classes;

use Books\Book\Classes\Enums\SortEnum;
use Books\Book\Classes\Enums\StatsEnum;
use Books\Book\Classes\Rater;
use Books\Book\Classes\WidgetService;
use Books\Book\Models\Book;
use Books\Book\Models\Tag;
use Books\Catalog\Models\Genre;
use Exception;
use October\Rain\Database\Builder;

class ListingService
{

    protected bool $useRater = false;
    protected bool $useWidgetService = false;
    protected bool $useWidgetSort = false;
    protected Builder $builder;
    protected bool $hasIncludeGenres = false;

    public function __construct(protected ListingFilter $filter)
    {
        $this->useRater = in_array($this->filter->sort, [SortEnum::popular_day, SortEnum::popular_week, SortEnum::popular_month]);
        $this->useWidgetService = $this->filter->widget?->isListable() ?? false;
        $this->useWidgetSort = $this->useWidgetService && !$this->useRater && $this->filter->widget->mapSortEnum() === $this->filter->sort;
        $this->builder = Book::query()->public()->defaultEager()->distinct((new Book())->getQualifiedKeyName());
        $this->hasIncludeGenres = $this->filter->includes(Genre::class)->count();
    }

    public function selfBind()
    {
        return $this->builder
            ->hasGenres($this->filter->excludes(Genre::class)->pluck('id')->toArray(), 'exclude')
            ->hasGenres($this->filter->includes(Genre::class)->pluck('id')->toArray())
            ->hasTags($this->filter->excludes(Tag::class)->pluck('id')->toArray(), 'exclude')
            ->hasTags($this->filter->includes(Tag::class)->pluck('id')->toArray())
            ->when($this->filter->complete, fn($q) => $q->complete())
            ->when(!$this->filter->free && $this->filter->min_price, fn($q) => $q->minPrice($this->filter->min_price))
            ->when(!$this->filter->free && $this->filter->max_price, fn($q) => $q->maxPrice($this->filter->max_price))
            ->when($this->filter->free, fn($q) => $q->free())
            ->when($this->filter->type, function ($q) {
                $q->whereHas('editions', function ($q) {
                    $q->where('type', $this->filter->type)->visible();
                });
            })
            ->when(!$this->useWidgetSort && !$this->useRater, fn($builder) => match ($this->filter->sort) {
                default => $builder->when($this->hasIncludeGenres,
                    fn($b) => $b->orderByGenresRate(...Genre::find($this->filter->includes(Genre::class)->pluck('id')->toArray()))
                )->sortByStatValue(StatsEnum::RATE),
                SortEnum::new => $builder->orderBySalesAt(),
                SortEnum::hotNew => $builder->sortByStatValue(StatsEnum::collected_hot_new_rate),
                SortEnum::gainingPopularity => $builder->sortByStatValue(StatsEnum::collected_gain_popularity_rate),
                SortEnum::topRate => $builder->sortByStatValue(StatsEnum::RATE),
                SortEnum::discount => $builder->orderByDiscountAmount(),
            });
    }

    /**
     * @throws Exception
     */
    public function bindByWidgetService(): void
    {
        $service = new WidgetService($this->filter->widget);

        $this->builder = $service
            ->setBuilder($this->builder)
            ->setDisableCache(true)
            ->setUseSort($this->useWidgetSort)
            ->applyEnum();
    }

    /**
     * @throws Exception
     */
    public function bindByRater(): void
    {
        $r = new Rater();
        $r->setOfLastDays(match ($this->filter->sort) {
            SortEnum::popular_day => 1,
            SortEnum::popular_week => 7,
            SortEnum::popular_month => 30,
            default => throw new Exception('????')
        });
        $r->setBuilder($this->builder);
        $r->applyAllStats();
        $r->performClosures();
        $seq = $r->getResult()->sortByDesc(fn(Book $book) => $book->stats->popular())->pluck('id')->toArray();
        $this->builder->orderByRaw('FIELD (' . (new Book())->getQualifiedKeyName() . ', ' . implode(', ', $seq) . ') ASC');
    }

    /**
     * @throws Exception
     */
    public function applyScopes()
    {
        $this->selfBind();

        if ($this->useWidgetService) {
            $this->bindByWidgetService();
        }

        if ($this->useRater) {
            $this->bindByRater();
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

}
