<?php namespace Books\Catalog\Components;

use Lang;
use Flash;
use Cookie;
use Request;
use Response;
use Exception;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use Books\Catalog\Models\Genre;
use Illuminate\Support\Facades\RateLimiter;
use Books\Catalog\Classes\FavoritesManager;

/**
 * FavoriteGenres Component
 */
class FavoriteGenres extends ComponentBase
{
    private $user = null;
    private array $favorite_genres = array();

    public function componentDetails()
    {
        return [
            'name' => 'FavoriteGenres',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        parent::init();
        $this->user = Auth::getUser();
        if (!$this->user && !Cookie::has('favorite_genres')) {
            $this->favorite_genres = (new FavoritesManager())->getDefaultGenres();
        } else {
            $this->favorite_genres = $this->user?->favorite_genres ?? json_decode(Cookie::get('favorite_genres')) ?? [];
        }
    }

    public function favoriteGenres()
    {
        return Genre::query()
            ->active()
            ->favorite()
            ->select(['id', 'name'])
            ->get()
            ->each(function ($i) {
                $i['selected'] = in_array($i->id, $this->favorite_genres);
            })
            ->split(4);

    }

    public function onToggleFavorite(): \Illuminate\Http\Response
    {

        $attempts = $this->user ? 30 : 15;
        if (!RateLimiter::attempt('toggleFavorite' . request()->ip(), $attempts, fn() => 1)) {
            $ex = new Exception(Lang::get('books.catalog::lang.plugin.too_many_attempt'));
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }

        $id = post('id');
        $this->favorite_genres = array_values(
            in_array($id, $this->favorite_genres)
                ? array_diff($this->favorite_genres, [$id])
                : array_merge($this->favorite_genres, [$id])
        );

        $response = Response::make('');
        if ($this->user) {
            (new FavoritesManager())->save($this->user, $this->favorite_genres);

            return $response;
        }

        return $response->withCookie(Cookie::forever('favorite_genres', json_encode($this->favorite_genres)));
    }
}
