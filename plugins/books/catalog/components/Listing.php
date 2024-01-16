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
use Books\Catalog\Models\Type;
use Cms\Classes\ComponentBase;
use Exception;
use Illuminate\Support\Collection;
use RainLab\User\Facades\Auth;
use Redirect;
use Request;

/**
 * Listing Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Listing extends ComponentBase
{
    protected ListingFilter $filter;

    protected int $trackInputTime = 620;

    protected int $perPage = 15;

    protected ?Genre $categoryGenre = null;

    protected ?Type $categoryType = null;

    protected Genre|Type|null $categorySlugModel = null;

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
        $this->categoryType = Type::find((int) get('type'));
    }

    public function onRun()
    {
        if ($redirectToSlug = $this->redirectToSlug()) {
            return Redirect::to($redirectToSlug);
        }

        if (! $this->appliedSlug()) {
            abort(404);
        }
    }

    public function onRender()
    {
        $this->page['bind'] = $this->getBind();
        $this->page['category_slug_model'] = $this->categorySlugModel;
        $this->page['genre'] = $this->categorySlugModel ?? $this->categoryGenre;

        $this->setSEOFromSlugModel();
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
                ->whereNotIn('id', $this->filter->byClass(Genre::class)->pluck('id')->toArray())
                ->get(),
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
    public function books(): CustomPaginator
    {
        $builder = (new ListingService($this->filter))
            ->applyScopes()
            ->getBuilder()
            ->paginate(perPage: $this->perPage, page: $this->filter->page);

        return CustomPaginator::from($builder)
            ->setHandler($this->alias.'::onSearch')
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


    private function redirectToSlug(): ?string
    {
        if (is_null($this->param('category_slug'))) {
            $genreId = get('genre');
            $typeId = get('type');

            if ($genreId && is_numeric($genreId)) {
                return $this->getSlugFromBookGenre($genreId);
            } elseif ($typeId && is_numeric($typeId)) {
                return $this->getSlugFromBookType($typeId);
            }
        }

        return null;
    }

    /**
     * Жанр имеет приоритет перед типом
     */
    private function appliedSlug(): bool
    {
        $categorySlug = $this->param('category_slug');

        if ($categorySlug) {
            if ($this->appliedSlugFromGenre($categorySlug)) {
                return true;
            }
            if ($this->appliedSlugFromType($categorySlug)) {
                return true;
            }

            return false;
        }

        return true;
    }

    private function setSEOFromSlugModel(): void
    {
        if ($slugModel = $this->categorySlugModel) {
            $name = match (true) {
                $slugModel instanceof Genre => $slugModel->name,
                $slugModel instanceof Type => $slugModel->type->label(),
                default => '',
            };

            $this->page->h1 = $slugModel->h1;

            $this->page->meta_title = $slugModel->meta_title
                ?? "{$name} – скачать новинки в fb2, epub, txt, pdf или читать онлайн бесплатно полные";

            $this->page->meta_description = $slugModel->meta_desc
                ?? "Электронная библиотека “Время книг” предлагает скачать книги жанра «{$name}» в fb2, epub, txt, pdf или читать онлайн бесплатно";
        }

        $this->page->meta_canonical = $this->getCanonicalUrl();
    }

    private function getSlugFromBookGenre(int $genreId): ?string
    {
        $genre = Genre::where('id', $genreId)->first();

        if (! $genre) {
            return null;
        }

        $this->categorySlugModel = $genre;

        if ($genre->slug) {
            $redirectToSlug = '/listing/'.$genre->slug;

            $getParams = get();
            $queryParams = array_filter($getParams, function ($param) {
                return $param != 'genre';
            }, ARRAY_FILTER_USE_KEY);

            $queryString = ! empty($queryParams) ? '?'.http_build_query($queryParams) : '';

            return $redirectToSlug.$queryString;
        }

        return null;
    }

    private function getSlugFromBookType(int $typeId): ?string
    {
        $type = Type::where('id', $typeId)->first();

        if (! $type) {
            return null;
        }

        $this->categorySlugModel = $type;

        if ($type->slug) {
            $redirectToSlug = '/listing/'.$type->slug;

            $getParams = get();
            $queryParams = array_filter($getParams, function ($param) {
                return $param != 'type';
            }, ARRAY_FILTER_USE_KEY);

            $queryString = ! empty($queryParams) ? '?'.http_build_query($queryParams) : '';

            return $redirectToSlug.$queryString;
        }

        return null;
    }

    private function appliedSlugFromGenre(string $categorySlug): bool
    {
        $genre = Genre::slug($categorySlug)->first();
        if ($genre) {
            $this->filter->fromParams(['genreSlug' => $genre->id]);
            $this->categorySlugModel = $genre;

            return true;
        }

        return false;
    }

    private function appliedSlugFromType(string $categorySlug): bool
    {
        $type = Type::slug($categorySlug)->first();
        if ($type) {
            $this->filter->fromParams(['typeSlug' => $type->id]);
            $this->categorySlugModel = $type;

            return true;
        }

        return false;
    }

    private function getCanonicalUrl(): string
    {
        $canonicalParams = ['genre', 'type'];

        $currentPageUrl = Request::url();
        $getParams = get();

        $filterCanonicalParams = array_filter($getParams, function ($param) use ($canonicalParams) {
            return in_array($param, $canonicalParams);
        }, ARRAY_FILTER_USE_KEY);

        return $currentPageUrl
            .(! empty($filterCanonicalParams) ? '?'.http_build_query($filterCanonicalParams) : '');
    }
}
