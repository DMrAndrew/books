<?php

namespace Books\Catalog\Components;

use Books\Catalog\Classes\RecommendsService;
use Books\Catalog\Models\Genre;
use Books\User\Classes\CookieEnum;
use Cms\Classes\ComponentBase;
use Flash;
use October\Rain\Database\Collection;
use RainLab\User\Facades\Auth;

/**
 * FavoriteGenres Component
 */
class Recommends extends ComponentBase
{
    private $user = null;

    private Collection $loved_genres;

    private $unloved_genres;
    protected RecommendsService $manager;

    public function componentDetails()
    {
        return [
            'name' => 'FavoriteGenres',
            'description' => 'No description provided yet...',
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->user = Auth::getUser();
        $this->manager = new RecommendsService(post('recommend'));
        $this->prepareVals();
    }

    public function prepareVals()
    {
        if ($this->user) {
            $this->loved_genres = Genre::find($this->user->loved_genres ?? []);
            $this->unloved_genres = Genre::find($this->user->unloved_genres ?? []);
        } else {
            $this->loved_genres = Genre::find($this->manager->lovedFromRecommend());
            $this->unloved_genres = Genre::find($this->manager->unlovedFromRecommend());
        }

        $this->page['nestedGenres'] = $this->nestedGenres();
        $this->page['loved_genres'] = $this->loved_genres;
        $this->page['unloved_genres'] = $this->unloved_genres;
    }


    public function nestedGenres()
    {
        $roots = Genre::query()
            ->public()
            ->select(['id', 'name'])
            ->with('children')
            ->roots()
            ->get();

        $this->mergeWithSelected($roots);
        $roots->each(function ($root) {
            $this->mergeWithSelected($root->children);
        });

        return $roots;
    }

    public function mergeWithSelected(Collection $genres): Collection
    {
        $genres->each(function ($i) {
            $i['loved'] = (bool)$this->loved_genres->intersect([$i])->count();
            $i['unloved'] = (bool)$this->unloved_genres?->intersect([$i])->count();
        });

        return $genres;
    }


    public function onChangeGenres()
    {
        if ($this->user) {
            $this->manager->save($this->user);
            Flash::success('Любимые жанры успешно сохранены');
            $this->prepareVals();
        } else {
            CookieEnum::RECOMMEND->setForever(post('recommend'));
        }

        return ['.recommend_spawn' => $this->renderPartial('personal-area/recommend_section', [
            'loved_genres' => $this->loved_genres,
            'unloved_genres' => $this->unloved_genres,
        ])];
    }
}
