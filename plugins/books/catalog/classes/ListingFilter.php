<?php

namespace Books\Catalog\Classes;

use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Enums\SortEnum;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Models\Tag;
use Books\Catalog\Models\Genre;
use Books\Catalog\Models\Type;
use Cache;
use Illuminate\Support\Collection;
use Model;

class ListingFilter
{
    protected Collection $filters;

    public ?EditionsEnums $type = null;

    public bool $free = false;

    public bool $complete = false;

    public ?int $min_price = null;

    public ?int $max_price = null;

    public ?WidgetEnum $widget = null;

    public ?SortEnum $sort = null;

    public function __construct(protected ?string $session_key = null)
    {
        $this->filters = collect();
        if (!$this->getSessionKey()) {
            $this->fromQuery();
        } else {
            $this->type = post('type') ? EditionsEnums::tryFrom(post('type') ?? '') : null;
            $this->widget = WidgetEnum::tryFrom(post('widget') ?? '') ?? null;
            $this->sort = $this->sortFromString(post('sort'));
            $this->complete = post('complete_only') == 'on';
            $this->free = post('free') == 'on';
            $this->max_price = (int)post('max_price') ?: null;
            $this->min_price = (int)post('min_price') ?: null;
            $this->filters = collect(Cache::get($this->getSessionKey()) ?? []);
        }
    }

    public function fromQuery(): void
    {
        $query = collect(request()->query())->only(['type', 'genre', 'tag', 'widget']);

        $this->include($this->fromPost(Tag::class, $query['tag'] ?? null));
        $this->include($this->fromPost(Genre::class, $query['genre'] ?? null));
        $this->type = ($query['type'] ?? null) ? EditionsEnums::tryFrom($query['type']??null) : null;
        $this->widget = WidgetEnum::tryFrom($query['widget'] ?? '');
        $this->sort = $this->sortFromString($query['sort']??null);
    }

    public function fromParams(array $params): void
    {
        if (isset($params['genreSlug'])) {
            $this->include($this->fromPost(Genre::class, $params['genreSlug'] ?? null));
        } else if (isset($params['typeSlug'])) {
            $this->type = EditionsEnums::tryFrom($params['typeSlug'] ?? '');
        }
    }

    public function sortFromString(?string $val = null): SortEnum
    {
        return SortEnum::tryFrom($val ?? '') ?? $this->widget?->mapSortEnum() ?? SortEnum::default();
    }

    public function save(): void
    {
        if ($this->getSessionKey()) {
            Cache::put($this->getSessionKey(), $this->filters->toArray());
        }
    }

    /**
     * @return Collection
     */
    public function getFilters(): Collection
    {
        return $this->filters;
    }

    public function toBind(): array
    {
        return array_merge((array)$this, [
            'include_genres' => $this->includes(Genre::class),
            'exclude_genres' => $this->excludes(Genre::class),
            'include_tags' => $this->includes(Tag::class),
            'exclude_tags' => $this->excludes(Tag::class),
        ]);
    }

    public function includes(string $model): \October\Rain\Support\Collection|Collection
    {
        return $this->byClass($model)->where('flag', 'include');
    }

    public function excludes(string $model): \October\Rain\Support\Collection|Collection
    {
        return $this->byClass($model)->where('flag', 'exclude');
    }

    public function push(?Model $model, string $type): void
    {
        if (!$model) {
            return;
        }
        $class = get_class($model);
        if ($this->byClass($class)->whereIn('id', [$model->id])->count() > 0) {
            return;
        }
        $model['class'] = $class;
        $model['flag'] = $type;
        $this->filters->push($model);
        $this->save();
    }

    public function sync(Collection $models, string $class, string $type)
    {
        $this->removeAll($class, $type);
        foreach ($models as $model) {
            $this->push($model, $type);
        }
    }


    public function removeInclude(Model $model): void
    {
        $this->remove($model, 'include');
    }

    public function removeExclude(Model $model): void
    {
        $this->remove($model, 'exclude');
    }

    public function remove(Model $model, string $flag): void
    {
        $this->filters = $this->filters->reject(function ($item) use ($model, $flag) {
            return $item['flag'] == $flag && $item['class'] == get_class($model) && $item['id'] == $model->id;
        });
        $this->save();
    }

    public function removeAllInclude(string $class): void
    {
        $this->removeAll($class, 'include');
    }

    public function removeAllExclude(string $class): void
    {
        $this->removeAll($class, 'exclude');
    }

    public function removeAll(string $class, string $flag): void
    {
        $this->filters = $this->filters->reject(function ($item) use ($class, $flag) {
            return $item['class'] == $class && $item['flag'] == $flag;
        });
        $this->save();
    }

    public function byClass(string $class): \October\Rain\Support\Collection|Collection
    {
        return $this->filters->where('class', $class);
    }

    public function byFlag(string $flag): \October\Rain\Support\Collection|Collection
    {
        return $this->filters->where('flag', $flag);
    }

    public function include(?Model $model): void
    {
        $this->push($model, 'include');
    }

    public function exclude(?Model $model): void
    {
        $this->push($model, 'exclude');
    }

    public function fromPost(string $class, int|array|null|string $id = null)
    {
        return $this->query($class)->find($id ?? post('item')['id'] ?? post('remove_id'));
    }

    public function query(string $class)
    {
        return $class::query()->public()->asOption();
    }

    public function syncFromPost(string $class, string $flag)
    {
        $this->sync($this->fromPost($class, collect(post('items'))->pluck('value')->toArray()), $class, $flag);
    }

    public function getSessionKey()
    {
        return post('_session_key') ?? $this->session_key;
    }
}
