<?php

namespace Books\Catalog\Classes;

use Cookie;
use RainLab\User\Models\User;
use RainLab\User\Facades\Auth;
use Books\Catalog\Models\Genre;

class FavoritesManager
{
    /**
     * @param User|null $user
     * @param array|null $array
     * @return void
     */
    public function save(?User $user = null, ?array $array = null): void
    {
        $user ??= Auth::getUser();
        if ($user) {
            $user->favorite_genres = $array ?? (Cookie::has('favorite_genres') ? json_decode(Cookie::get('favorite_genres')) : $this->getDefaultGenres());
            $user->save(['force' => true]);
        }
    }

    /**
     * Default favorite genres ids array
     *
     * @return array
     */
    public function getDefaultGenres(): array
    {
        return Genre::query()
            ->active()
            ->favorite()
            ->select(['id'])
            ->get()
            ->pluck('id')
            ->toArray();
    }

}
