<?php

namespace App\middleware;

use Abordage\LastModified\Facades\LastModified;
use Books\User\Classes\CookieEnum;
use Cookie;
use Illuminate\Support\Carbon;
use RainLab\User\Facades\Auth;

class FetchCheckUp
{
    public function handle($request, $next)
    {
        $response = $next($request);
        LastModified::set(Carbon::parse('2023-07-07 13:29'));
        if (Auth::getUser()?->fetchRequired()) {
            return $response->withCookie(Cookie::make(CookieEnum::FETCH_REQUIRED->value, 1, httpOnly: false));
        }

        return $response;
    }
}
