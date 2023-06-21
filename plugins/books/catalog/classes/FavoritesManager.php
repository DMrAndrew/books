<?php

namespace Books\Catalog\Classes;

use Books\Catalog\Models\Genre;
use Books\User\Classes\CookieEnum;
use Cookie;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

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
            $user->loved_genres = $loved ?? (Cookie::has(CookieEnum::LOVED_GENRES->value) ? json_decode(Cookie::get(CookieEnum::LOVED_GENRES->value)) : $this->getDefaultGenres());
            $user->unloved_genres = $unloved ?? (Cookie::has(CookieEnum::UNLOVED_GENRES->value) ? json_decode(Cookie::get(CookieEnum::UNLOVED_GENRES->value)) : []);
            $user->save(['force' => true]);
        }
    }

    /**
     * Default favorite genres ids array
     *
     */
    public static function defaultGenresBuilder()
    {
        return Genre::query()
            ->public()
            ->favorite();
    }

    public static function getDefaultGenresIds(): array
    {
        return static::defaultGenresBuilder()
            ->select(['id'])
            ->get()
            ->pluck('id')
            ->toArray();
    }
}
