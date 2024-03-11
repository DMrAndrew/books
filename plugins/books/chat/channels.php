<?php

use RainLab\User\Facades\Auth;

Broadcast::channel('profile.{id}', function ($user, $id) {
    return Auth::getUser()?->profile->id == $id;
});
