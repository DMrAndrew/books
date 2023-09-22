<?php

namespace Books\Catalog\Components;

use App\classes\CustomPaginator;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Enums\SortEnum;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Models\Tag;
use Books\Catalog\Classes\ListingFilter;
use Books\Catalog\Classes\ListingService;
use Books\Catalog\Models\Genre;
use Cms\Classes\ComponentBase;
use Exception;
use Illuminate\Support\Collection;
use RainLab\User\Facades\Auth;
use Redirect;

/**
 * Listing Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Listing extends ComponentBase
{
    protected ListingFilter $filter;

    protected int $trackInputTime = 620;

    protected int $perPage = 12;

    protected ?Genre $categoryGenre = null;
    protected ?Genre $categorySlugGenre = null;

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
        $this->page['listable'] = WidgetEnum::listable();

        $this->categoryGenre = Genre::find((int) get('genre'));
    }

    public function onRun()
    {
        if ($redirectToSlug = $this->redirectToGenreSlug()) {
            return Redirect::to($redirectToSlug);
        };

        if ( !$this->applyGenreSlug()) {
            abort(404);
        }
    }

    public function onRender()
    {
        $this->page['bind'] = $this->getBind();
        $this->page['category_slug_genre'] = $this->categorySlugGenre;
        $this->page['$genre'] = $this->categorySlugGenre ?? $this->categoryGenre;

        $this->setSEOFromGenre();
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
            'genres_list' => $this->filter
                ->query(Genre::class)
                ->whereNotIn('id', $this->filter->byClass(Genre::class)
                    ->pluck('id')
                    ->toArray())
                ->get()
        ]);
    }

    public function onSearchIncludeGenre()
    {
        return $this->renderOptions($this->byTerm(Genre::class), ['handler' => $this->alias . '::onAddIncludeGenre']);
    }

    public function onSearchExcludeGenre()
    {
        return $this->renderOptions($this->byTerm(Genre::class), ['handler' => $this->alias . '::onAddExcludeGenre']);
    }

    public function onSearchIncludeTag()
    {
        return $this->renderOptions($this->byTerm(Tag::class), ['handler' => $this->alias . '::onAddIncludeTag']);
    }

    public function onSearchExcludeTag()
    {
        return $this->renderOptions($this->byTerm(Tag::class), ['handler' => $this->alias . '::onAddExcludeTag']);
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
        $this->filter->syncFromPost(Genre::class, 'include');
        return $this->onSearch();
    }

    public function onAddExcludeGenre()
    {
        $this->filter->syncFromPost(Genre::class, 'exclude');
        return $this->onSearch();
    }

    public function onAddIncludeGenreOld()
    {
        $this->filter->include($this->filter->fromPost(Genre::class));
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
    public function books(): \App\classes\CustomPaginator
    {
        return CustomPaginator::from(
            (new ListingService($this->filter))
                ->applyScopes()
                ->getBuilder()
                ->paginate($this->perPage))
            ->setHandler($this->alias . '::onSearch')
            ->setScrollToContainer('.book-card');
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

    /**
     * @return string|null
     */
    private function redirectToGenreSlug(): ?string
    {
        $genreId = get('genre');
        if ($genreId && is_numeric($genreId)) {
            $genre = Genre::where('id', $genreId)->first();

            if (!$genre) {
                return null;
            }

            $categorySlug = (string)$genre->slug;

            if ($categorySlug) {
                $redirectToSlug = '/listing/' . $genre->slug;

                $getParams = get();
                $queryParams = array_filter($getParams, function($param) {
                    return $param != 'genre';
                }, ARRAY_FILTER_USE_KEY );

                $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';

                return $redirectToSlug . $queryString;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    private function applyGenreSlug(): bool
    {
        $categorySlug = $this->param('category_slug');
        if ($categorySlug) {
            $genre = Genre::slug($categorySlug)->first();

            if (!$genre) {
                return false;
            }

            $this->categorySlugGenre = $genre;
            $this->filter->fromParams(['genreSlug' => $genre->id]);
        }

        return true;
    }

    /**
     * @return void
     */
    private function setSEOFromGenre(): void
    {
        $genre = $this->categorySlugGenre ?? $this->categoryGenre;
        if ($genre) {
            $this->page->h1 = $genre->h1 ?? $genre->name; //dd($this->page->h1);
            $this->page->meta_title = "{$genre->name} – скачать новинки в fb2, epub, txt, pdf или читать онлайн бесплатно полные";
            $this->page->meta_description = "Электронная библиотека “Время книг” предлагает скачать книги жанра «{$genre->name}» в fb2, epub, txt, pdf или читать онлайн бесплатно";
        }
    }
}
