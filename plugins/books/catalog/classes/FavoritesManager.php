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
     * @param array|null $loved
     * @return void
     */
    public function save(?User $user = null, ?array $loved = null, ?array $unloved = null): void
    {
        $user ??= Auth::getUser();
        if ($user) {
            $user->loved_genres = $loved ?? (Cookie::has('loved_genres') ? json_decode(Cookie::get('loved_genres')) : $this->getDefaultGenres());
            $user->unloved_genres = $unloved ?? (Cookie::has('unloved_genres') ? json_decode(Cookie::get('unloved_genres')) : []);
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
            ->public()
            ->favorite()
            ->select(['id'])
            ->get()
            ->pluck('id')
            ->toArray();
    }

}
