<?php

namespace App\middleware;

use Books\User\Classes\CookieEnum;
use Cookie;
use RainLab\User\Facades\Auth;

class FetchCheckUp
{
    public function handle($request, $next)
    {
        $response = $next($request);
        if (Auth::getUser()?->fetchRequired()) {
            return $response->withCookie(Cookie::make(CookieEnum::FETCH_REQUIRED->value, 1, httpOnly: false));
        }

        return $response;
    }
}
