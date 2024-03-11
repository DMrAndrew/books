<?php

namespace Books\AuthorPrograms\classes;

use Books\AuthorPrograms\Components\ModalNotification;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use RainLab\User\Facades\Auth;

class LoginHandler
{
    /**
     * Handle user login events.
     */
    public function userLogin($event)
    {
        if (Auth::user()?->birthday?->isBirthday()) {
            Cookie::queue('show_reader_birthday_program', true);
        }
        Cookie::queue('show_new_reader_program', true);
        Cookie::queue('show_regular_reader_program', true);
    }

    /**
     * Handle user logout events.
     */
    public function userLogout($event)
    {
        Cookie::expire('show_reader_birthday_program');
        Cookie::expire('show_new_reader_program');
        Cookie::expire('show_regular_reader_program');
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('rainlab.user.login', [LoginHandler::class, 'userLogin']);
        $events->listen('rainlab.user.logout', [LoginHandler::class, 'userLogout']);
    }
}
