<?php

namespace Books\Catalog\Classes;

use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Models\Tag;
use Books\Catalog\Models\Genre;
use Cache;
use Model;
use Illuminate\Support\Collection;

class ListingFilter
{
    protected Collection $filters;
    public ?EditionsEnums $type = null;
    public bool $free = false;
    public bool $complete = false;
    public ?int $min_price = null;
    public ?int $max_price = null;

    public function __construct(protected ?string $session_key = null)
    {
        $this->filters = collect();
        if (!$this->getSessionKey()) {
            $this->fromQuery();

        } else {
            $this->type = post('type') ? EditionsEnums::tryFrom(post('type')) : null;
            $this->complete = post('complete_only') == 'on';
            $this->free = post('free') == 'on';
            $this->max_price = (int)post('max_price') ?: null;
            $this->min_price = (int)post('min_price') ?: null;
            $this->filters = collect(Cache::get($this->getSessionKey()) ?? []);
        }

    }

    public function fromQuery()
    {
        $query = collect(request()->query())->only(['type', 'genre', 'tag']);
        $this->include($this->fromPost(Tag::class, $query['tag'] ?? null));
        $this->include($this->fromPost(Genre::class, $query['genre'] ?? null));
        $this->type = ($query['type'] ?? null) ? EditionsEnums::tryFrom($query['type']) : null;
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
        return (array)$this + [
                'include_genres' => $this->includes(Genre::class),
                'exclude_genres' => $this->excludes(Genre::class),
                'include_tags' => $this->includes(Tag::class),
                'exclude_tags' => $this->excludes(Tag::class),
            ];
    }

    public function includes(string $model)
    {
        return $this->byClass($model)->where('flag', 'include');
    }

    public function excludes(string $model)
    {
        return $this->byClass($model)->where('flag', 'exclude');
    }

    public function push(?Model $model, string $type)
    {
        if (!$model) {
            return;
        }
        $model['class'] = get_class($model);
        $model['flag'] = $type;
        $this->filters->push($model);
        $this->save();
    }

    public function removeInclude(Model $model)
    {
        $this->remove($model, 'include');
    }

    public function removeExclude(Model $model): void
    {
        $this->remove($model, 'exclude');
    }

    public function remove(Model $model, string $flag)
    {
        $this->filters = $this->filters->reject(function ($item) use ($model, $flag) {
            return $item['flag'] == $flag && $item['class'] == get_class($model) && $item['id'] == $model->id;
        });
        $this->save();
    }

    public function removeAllInclude(string $class)
    {
        $this->removeAll($class, 'include');
    }

    public function removeAllExclude(string $class)
    {
        $this->removeAll($class, 'exclude');
    }


    public function removeAll(string $class, string $flag)
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

    public function include(?Model $model)
    {
        $this->push($model, 'include');
    }


    public function exclude(?Model $model)
    {
        $this->push($model, 'exclude');
    }

    public function fromPost(string $class, ?int $id = null)
    {
        return $class::query()->asOption()->find($id ?? post('item')['id'] ?? post('remove_id'));
    }


    public function getSessionKey()
    {
        return post('_session_key') ?? $this->session_key;
    }


}
