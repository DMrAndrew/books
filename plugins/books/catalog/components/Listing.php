<?php

namespace Books\Catalog\Components;

use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Enums\SortEnum;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Classes\WidgetService;
use Books\Book\Models\Book;
use Books\Book\Models\Tag;
use Books\Catalog\Classes\ListingFilter;
use Books\Catalog\Models\Genre;
use Cms\Classes\ComponentBase;
use Exception;
use Illuminate\Support\Collection;
use RainLab\User\Facades\Auth;

/**
 * Listing Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Listing extends ComponentBase
{
    protected ListingFilter $filter;

    protected int $trackInputTime = 620;

    public function componentDetails()
    {
        return [
            'name' => 'Listing Component',
            'description' => 'No description provided yet...',
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->filter = new ListingFilter();
        $this->page['types'] = EditionsEnums::toArray();
    }

    public function onRender()
    {
        $this->page['bind'] = $this->getBind();
    }

    public function onInitQueryString()
    {
        $this->filter->fromQuery();
        $this->filter->save();
    }

    public function getBind()
    {
        return array_merge($this->filter->toBind(), [
            'books' => $this->books(),
            'trackInputTime' => $this->trackInputTime,
            'sorts' => SortEnum::cases(),
            'user' => Auth::getUser(),
        ]);
    }

    public function onSearchIncludeGenre()
    {
        return $this->renderOptions($this->byTerm(Genre::class), ['handler' => $this->alias.'::onAddIncludeGenre']);
    }

    public function onSearchExcludeGenre()
    {
        return $this->renderOptions($this->byTerm(Genre::class), ['handler' => $this->alias.'::onAddExcludeGenre']);
    }

    public function onSearchIncludeTag()
    {
        return $this->renderOptions($this->byTerm(Tag::class), ['handler' => $this->alias.'::onAddIncludeTag']);
    }

    public function onSearchExcludeTag()
    {
        return $this->renderOptions($this->byTerm(Tag::class), ['handler' => $this->alias.'::onAddExcludeTag']);
    }

    public function onAddIncludeTag()
    {
        $this->filter->include($this->filter->fromPost(Tag::class));

        return $this->onSearch();
    }

    public function onAddExcludeTag()
    {
        $this->filter->exclude($this->filter->fromPost(Tag::class));

        return $this->onSearch();
    }

    public function onAddIncludeGenre()
    {
        $this->filter->include($this->filter->fromPost(Genre::class));

        return $this->onSearch();
    }

    public function onAddExcludeGenre()
    {
        $this->filter->exclude($this->filter->fromPost(Genre::class));

        return $this->onSearch();
    }

    public function onRemoveIncludeGenre()
    {
        $this->filter->removeInclude($this->filter->fromPost(Genre::class));

        return $this->onSearch();
    }

    public function onRemoveExcludeGenre()
    {
        $this->filter->removeExclude($this->filter->fromPost(Genre::class));

        return $this->onSearch();
    }

    public function onRemoveIncludeTag()
    {
        $this->filter->removeInclude($this->filter->fromPost(Tag::class));

        return $this->onSearch();
    }

    public function onRemoveExcludeTag()
    {
        $this->filter->removeExclude($this->filter->fromPost(Tag::class));

        return $this->onSearch();
    }

    public function onRemoveAllIncludeGenre()
    {
        $this->filter->removeAllInclude(Genre::class);

        return $this->onSearch();
    }

    public function onRemoveAllExcludeGenre()
    {
        $this->filter->removeAllExclude(Genre::class);

        return $this->onSearch();
    }

    public function onRemoveAllExcludeTag()
    {
        $this->filter->removeAllExclude(Tag::class);

        return $this->onSearch();
    }

    public function onRemoveAllIncludeTag()
    {
        $this->filter->removeAllInclude(Tag::class);

        return $this->onSearch();
    }

    public function byTerm(string $class)
    {
        $term = post('term');

        return $class::nameLike($term)
            ->public()
            ->asOption()
            ->whereNotIn('id', $this->filter->byClass($class)->pluck('id')->toArray())
            ->get();
    }

    public function onSearch()
    {
        return [
            '#listing-form' => $this->renderPartial('@listing-form-view', [
                'bind' => $this->getBind(),
            ]),
        ];
    }

    /**
     * @throws Exception
     */
    public function books()
    {
        $query = Book::query()
            ->hasGenres($this->filter->excludes(Genre::class)->pluck('id')->toArray(), 'exclude')
            ->hasGenres($this->filter->includes(Genre::class)->pluck('id')->toArray())
            ->hasTags($this->filter->excludes(Tag::class)->pluck('id')->toArray(), 'exclude')
            ->hasTags($this->filter->includes(Tag::class)->pluck('id')->toArray())
            ->when($this->filter->complete, fn ($q) => $q->complete())
            ->when(! $this->filter->free && $this->filter->min_price, fn ($q) => $q->minPrice($this->filter->min_price))
            ->when(! $this->filter->free && $this->filter->max_price, fn ($q) => $q->maxPrice($this->filter->max_price))
            ->when($this->filter->free, fn ($q) => $q->free())
            ->when($this->filter->type, fn ($q) => $q->type($this->filter->type))
            ->public()
            ->defaultEager();

        $books = null;
        if ($this->filter->widget && in_array($this->filter->widget, [
            WidgetEnum::recommend,
            WidgetEnum::hotNew,
            WidgetEnum::new,
            WidgetEnum::gainingPopularity])) {
            $service = new WidgetService($this->filter->widget);

            $books = $service
                ->setQuery($query)
                ->setUseSort(! in_array($this->filter->widget, [
                    WidgetEnum::hotNew,
                    WidgetEnum::new,
                    WidgetEnum::gainingPopularity]))
                ->collect()
                ->sort()
                ->getValues();
        }

        $books ??= $query->get();

        return match ($this->filter->sort) {
            SortEnum::popular_day, SortEnum::popular_week, SortEnum::popular_month => $books->sortByDesc(fn ($book) => $book->stats->popular()),
            SortEnum::new => Book::sortCollectionBySalesAt($books),
            SortEnum::hotNew, SortEnum::gainingPopularity => $books->sortByDesc(fn ($book) => $book->getCollectedRate($this->filter->sort === SortEnum::hotNew ? WidgetEnum::hotNew : WidgetEnum::gainingPopularity)),
            SortEnum::topRate => $books->sortByDesc(fn ($book) => $book->stats->rate),
            default => $books
        };
    }

    public function renderOptions(Collection $options, array $itemOptions = []): array
    {
        return $options->map(function ($item) use ($itemOptions) {
            return $itemOptions + [
                'id' => $item['id'],
                'label' => $item['name'],
                'htm' => $this->renderPartial('select/option', ['label' => $item['name']]),
            ];
        })->toArray();
    }

    public function getSessionKey()
    {
        return post('_session_key');
    }
}
