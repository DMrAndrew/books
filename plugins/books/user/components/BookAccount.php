<?php

namespace Books\User\Components;

use App\classes\PartialSpawns;
use Books\User\Classes\CookieEnum;
use Books\User\Classes\UserService;
use Books\User\Models\TempAdultPass;
use Cookie;
use Illuminate\Http\RedirectResponse;
use RainLab\User\Components\Account;
use RainLab\User\Models\User;
use Redirect;

class BookAccount extends Account
{
    public function componentDetails()
    {
        return [
            'name' => 'Book Account Component',
            'description' => 'Extend rainlab user account component',
        ];
    }

    public function onAdultAgreementSave(): RedirectResponse
    {
        $agree = post('action') === 'accept';
        if ($this->user()) {
            $this->user()->update(['see_adult' => $agree, 'asked_adult_agreement' => 1]);
        } else {
            if (UserService::canGuestBeAskedAdultPermission()) {
                (TempAdultPass::make(['is_agree' => $agree]))->save();
            }
        }

        return Redirect::refresh();
    }


    public function onGetGuestRegisterForm(): array
    {
        if ($data = CookieEnum::guest->get()) {
            return [
                PartialSpawns::SPAWN_MODAL->value => $this->renderPartial('auth/registerForm', ['user' => new User($data)]),
            ];
        }
        return [];
    }

    public function onGetRegisterForm(): array
    {
        return [
            PartialSpawns::SPAWN_MODAL->value => $this->renderPartial('auth/registerForm'),
        ];

    }

    public function onGetLoginPopup(): array
    {
        return [
            PartialSpawns::SPAWN_MODAL->value => $this->renderPartial('auth/loginPopup'),
        ];

    }

    public function onGetRegisterPopup(): array
    {
        return [
            PartialSpawns::SPAWN_MODAL->value => $this->renderPartial('auth/registerPopup'),
        ];

    }

    public function onGetLoginForm(): array
    {
        return [
            PartialSpawns::SPAWN_MODAL->value => $this->renderPartial('auth/loginForm'),
        ];

    }

    public function onFetch(): array
    {
        if ($this->user()?->requiredAskAdult()) {
            return [PartialSpawns::SPAWN_MODAL->value => $this->renderPartial('auth/adult-modal', ['active' => 1])];
        }

        return [];
    }
}
