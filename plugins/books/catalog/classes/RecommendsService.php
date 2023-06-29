<?php

namespace Books\Catalog\Classes;

use Books\Catalog\Models\Genre;
use Books\User\Classes\CookieEnum;
use Illuminate\Support\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

class RecommendsService
{

    public function __construct(public Collection|array|null $recommend = null)
    {
        $this->recommend = collect($this->recommend ?: (CookieEnum::RECOMMEND->get() ?? []));
    }

    public function save(?User $user = null, ?array $loved = null, ?array $unloved = null): void
    {
        $user ??= Auth::getUser();
        if ($user) {
            $user->loved_genres = $loved ?? $this->lovedFromRecommend();
            $user->unloved_genres = $unloved ?? $this->unlovedFromRecommend();
            $user->save();
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

    public function lovedFromRecommend(): array
    {
        return $this->recommend->filter(fn($i) => $i == 'on')->keys()->toArray();
    }

    public function unlovedFromRecommend(): array
    {
        return $this->recommend->filter(fn($i) => $i == 'off')->keys()->toArray();
    }


}
