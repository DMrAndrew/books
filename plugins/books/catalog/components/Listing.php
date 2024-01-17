<?php

namespace Books\Catalog\Components;

use App\classes\CustomPaginator;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Enums\SortEnum;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Models\Tag;
use Books\Catalog\Classes\ListingFilter;
use Books\Catalog\Classes\ListingParamHelper;
use Books\Catalog\Classes\ListingParamsEnum;
use Books\Catalog\Classes\ListingService;
use Books\Catalog\Models\Genre;
use Books\Catalog\Models\Type;
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
    protected string $urlPath = '/listing/';

    protected string $default_meta_title = '%s – скачать новинки в fb2, epub, txt, pdf или читать онлайн бесплатно полные';

    protected string $default_meta_desc = 'Электронная библиотека “Время книг” предлагает скачать книги жанра «%s» в fb2, epub, txt, pdf или читать онлайн бесплатно';

    protected ListingFilter $filter;

    protected int $trackInputTime = 620;

    protected int $perPage = 12;

    protected ?Genre $categoryGenre = null;

    protected ?Type $categoryType = null;

    protected Genre|Type|null $slugModel = null;

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
        if ($redirect = $this->redirectToSlug()) {
            return Redirect::to($redirect);
        }

        if (! $this->appliedSlug()) {
            abort(404);
        }
    }

    public function onRender()
    {
        $this->page['bind'] = $this->getBind();
        $this->page['category_slug_model'] = $this->slugModel;
        $this->page['genre'] = $this->slugModel ?? $this->categoryGenre;

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
                ->whereNotIn('id', $this->filter->byClass(Genre::class)
                    ->pluck('id')
                    ->toArray())
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
        $listingService = new ListingService($this->filter);

        return CustomPaginator::from(
                $listingService
                    ->applyScopes()
                    ->getBuilder()
                    ->paginate($this->perPage)
            )
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
            return $this->getSlugFromParam();
        }

        return null;
    }

    private function appliedSlug(): bool
    {
        $categorySlug = $this->param('category_slug');

        return ! $categorySlug || $this->applySlug($categorySlug);
    }

    private function setSEOFromSlugModel(): void
    {
        if ($this->slugModel) {
            $name = match (get_class($this->slugModel)) {
                Genre::class => $this->slugModel->name,
                Type::class => $this->slugModel->type->label(),
                default => '',
            };

            $this->setPageProps([
                'h1' => $this->slugModel->h1,
                'meta_title' => $this->slugModel->meta_title ?? sprintf($this->default_meta_title, $name),
                'meta_description' => $this->slugModel->meta_desc ?? sprintf($this->default_meta_desc, $name),
            ]);
        }
        $this->setPageProps([
            'meta_canonical' => $this->getCanonicalUrl(),
        ]);
    }

    private function getSlugFromParam(): ?string
    {
        $helper = ListingParamHelper::lookUp();
        if (! $helper || ! $helper->model->slug) {
            return null;
        }

        $this->slugModel = $helper->model;

        return $this->buildUrl($this->urlPath.$this->slugModel->slug, collect(get())->forget($helper->type->value));
    }

    private function applySlug(string $slag): bool
    {
        if (! $helper = ListingParamHelper::lookUp($slag)) {
            return false;
        }
        $this->filter->fromParams([$helper->type->filterKey() => $helper->model->id]);
        $this->slugModel = $helper->model;

        return true;
    }

    private function getCanonicalUrl(): string
    {
        return $this->buildUrl(request()->url(), collect(get())->only(ListingParamsEnum::values()->toArray()));

    }

    private function buildUrl(string $url = '', array|Collection $params = []): string
    {
        $params = collect($params);

        return implode('', [
            $url,
            $params->isEmpty() ? '' : '?',
            http_build_query($params->toArray()),
        ]);
    }

    private function setPageProps(array $attrs = []): void
    {
        foreach ($attrs as $attr => $val) {
            $this->page->{$attr} = $val;
        }
    }
}
