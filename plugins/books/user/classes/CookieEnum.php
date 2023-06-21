<?php

namespace Books\User\Classes;

enum CookieEnum: string
{
    case ADULT_ULID = 'adult_ulid';
    case FETCH_REQUIRED = 'fetch_required';
    case LOVED_GENRES = 'loved_genres';
    case UNLOVED_GENRES = 'unloved_genres';
}
