<?php

namespace App\middleware;

use Cookie;
use RainLab\User\Facades\Auth;

class FetchRequired
{
    public function handle($request, $next)
    {
        $response = $next($request);
        if (Auth::getUser()?->fetchRequired()) {
            return $response->withCookie(Cookie::make('fetch_required', 1, httpOnly: false));
        }

        return $response;
    }
}
