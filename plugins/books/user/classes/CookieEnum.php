<?php

namespace Books\User\Classes;

use Cookie;

enum CookieEnum: string
{
    case ADULT_ULID = 'adult_ulid';
    case FETCH_REQUIRED = 'fetch_required';
    case LOVED_GENRES = 'loved_genres';
    case UNLOVED_GENRES = 'unloved_genres';
    case RECOMMEND = 'recommend';

    public function setForever(string|array|null $value): void
    {
        Cookie::queue(Cookie::forever($this->value, json_encode($value)));
    }

    public function get()
    {
        $c = Cookie::get($this->value);
        return $c ? json_decode($c) : $c;
    }

    public function forget(): void
    {
        Cookie::queue(Cookie::forget($this->value));
    }
}
