<?php

namespace App\middleware;

use Closure;
use Cookie;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RainLab\User\Facades\Auth;

class FetchRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (Auth::getUser()?->fetchRequired()) {
            return $response->withCookie(Cookie::make('fetch_required', 1, httpOnly: false));
        }

        return $response;
    }
}
