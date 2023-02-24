<?php

namespace Books\Catalog\Components;

use Books\Catalog\Classes\FavoritesManager;
use Books\Catalog\Models\Genre;
use Cms\Classes\ComponentBase;
use Cookie;
use Exception;
use Flash;
use Illuminate\Support\Facades\RateLimiter;
use Lang;
use October\Rain\Database\Collection;
use RainLab\User\Facades\Auth;
use Request;
use Response;

/**
 * FavoriteGenres Component
 */
class FavoriteGenres extends ComponentBase
{
    private $user = null;

    private Collection $loved_genres;

    private $unloved_genres;

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
        if (! $this->user) {
            $this->loved_genres = Genre::find(Cookie::has('loved_genres') ? json_decode(Cookie::get('loved_genres')) : (new FavoritesManager())->getDefaultGenres());
            $this->unloved_genres = Genre::find(Cookie::has('unloved_genres') ? json_decode(Cookie::get('unloved_genres')) : []);
        } else {
            $this->loved_genres = Genre::find($this->user->loved_genres ?? []);
            $this->unloved_genres = Genre::find($this->user->unloved_genres ?? []);
        }
        $this->prepareVals();
    }

    public function prepareVals()
    {
        $this->page['genres'] = $this->genres();
        $this->page['nestedGenres'] = $this->nestedGenres();
        $this->page['loved_genres'] = $this->loved_genres;
        $this->page['unloved_genres'] = $this->unloved_genres;
    }

    public function queryGenres()
    {
        return Genre::query()
            ->public()
            ->select(['id', 'name']);
    }

    public function genres()
    {
        return $this->mergeWithSelected($this->queryGenres()->favorite()->get());
    }

    public function nestedGenres()
    {
        $roots = $this->queryGenres()
            ->nestedFavorites()
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
            $i['loved'] = (bool) $this->loved_genres->intersect([$i])->count();
            $i['unloved'] = (bool) $this->unloved_genres?->intersect([$i])->count();
        });

        return $genres;
    }

    public function onToggleFavorite(): \Illuminate\Http\Response
    {
        $attempts = 15;
        if (! RateLimiter::attempt('toggleFavorite'.request()->ip(), $attempts, fn () => 1)) {
            $ex = new Exception(Lang::get('books.catalog::lang.plugin.too_many_attempt'));
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }

        $genre = Genre::find(post('id'));
        if ($genre) {
            $this->loved_genres = (
                $this->loved_genres->intersect([$genre])->count()
                    ? $this->loved_genres->diff([$genre])
                    : $this->loved_genres->merge([$genre])
            );
        }

        $response = Response::make('');

        if ($this->user) {
            (new FavoritesManager())->save($this->user, $this->loved_genres->pluck('id')->toArray());

            return $response;
        }

        return $response->withCookie(Cookie::forever('loved_genres', json_encode($this->loved_genres->pluck('id')->toArray())))
            ->withCookie(Cookie::forever('unloved_genres', json_encode([])));
    }

    public function onChangeGenres()
    {
        $attempts = 15;
        if (! RateLimiter::attempt('toggleFavorite'.request()->ip(), $attempts, fn () => 1)) {
            $ex = new Exception(Lang::get('books.catalog::lang.plugin.too_many_attempt'));
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }

        $recommend = collect(post('recommend'));

        $this->loved_genres = Genre::find($recommend->filter(fn ($i) => $i == 'on')->keys());
        $this->unloved_genres = Genre::find($recommend->filter(fn ($i) => $i == 'off')->keys());

        $loved = $this->loved_genres->pluck('id')->toArray();
        $unloved = $this->unloved_genres->pluck('id')->toArray();

        if ($this->user) {
            (new FavoritesManager())->save($this->user, $loved, $unloved);
        }

        if ($this->user) {
            $partial = ['.recommend_spawn' => $this->renderPartial('personal-area/recommend_section', [
                'loved_genres' => $this->loved_genres,
                'unloved_genres' => $this->unloved_genres,
                'nestedGenres' => $this->nestedGenres(),
            ])];
        } else {
            $partial = ['.index_favorite_widget_spawn' => $this->renderPartial('genres/index_favorite_widget', [
                'genres' => $this->genres(),
            ])];
        }

        $response = Response::make($partial);

        if (! $this->user) {
            $response->
            withCookie(Cookie::forever('loved_genres', json_encode($loved)))->
            withCookie(Cookie::forever('unloved_genres', json_encode($unloved)));
        }

        return $response;
    }
}
