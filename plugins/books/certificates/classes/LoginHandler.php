<?php

namespace Books\Certificates\classes;

use Illuminate\Support\Facades\Cookie;
use RainLab\User\Facades\Auth;

class LoginHandler
{
    public function __invoke()
    {

        if (Auth::getUser()->profile->certificate_receiver()->notAcceptedCertificates()->count()) {
            Cookie::queue('show_certificate_modal', true);
        }
    }
}
