<?php

use RainLab\User\Facades\Auth;


    Route::get('/test', function () {
        $user = \Books\Profile\Models\Profile::find(307);
        dd($user->certificate_receiver()->notAcceptedCertificates()->count());
    });

