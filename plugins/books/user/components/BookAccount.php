<?php

namespace Books\User\Components;

use Books\User\Classes\CookieEnum;
use Books\User\Classes\UserService;
use Books\User\Models\TempAdultPass;
use Cookie;
use RainLab\User\Components\Account;
use RainLab\User\Models\Settings as UserSettings;
use RainLab\User\Models\User;
use Redirect;
use ValidationException;
use Validator;

class BookAccount extends Account
{
    public function componentDetails()
    {
        return [
            'name' => 'Book Account Component',
            'description' => 'Extend rainlab user account component',
        ];
    }

    public function onAdultAgreementSave()
    {
        $agree = post('action') === 'accept';
        if ($this->user()) {
            $this->user()->update(['see_adult' => $agree, 'asked_adult_agreement' => 1]);
        } else {
            if (UserService::canGuestBeAskedAdultPermission()) {
                $pass = TempAdultPass::make(['is_agree' => $agree]);
                $pass->save();
                Cookie::queue(Cookie::forever(CookieEnum::ADULT_ULID->value, $pass->id));
            }
        }

        return Redirect::refresh();
    }

    public function onRegister()
    {
        $redirect = parent::onRegister(); // TODO: Change the autogenerated stub
        CookieEnum::guest->forget();
        return $redirect;
    }


    public function onGetGuestRegisterForm()
    {
        if ($data = CookieEnum::guest->get()) {
            return [
                '#modal-spawn' => $this->renderPartial('auth/registerForm', ['user' => new User($data)]),
            ];
        }
        return [];
    }

    public function onGetRegisterForm()
    {
        return [
            '#modal-spawn' => $this->renderPartial('auth/registerForm'),
        ];

    }

    public function onGetLoginPopup()
    {
        return [
            '#modal-spawn' => $this->renderPartial('auth/loginPopup'),
        ];

    }

    public function onGetRegisterPopup()
    {
        return [
            '#modal-spawn' => $this->renderPartial('auth/registerPopup'),
        ];

    }

    public function onGetLoginForm()
    {
        return [
            '#modal-spawn' => $this->renderPartial('auth/loginForm'),
        ];

    }

    public function onFetch(): array
    {
        if ($this->user()?->requiredAskAdult()) {
            return ['#modal-spawn' => $this->renderPartial('auth/adult-modal', ['active' => 1])];
        }

        return [];
    }
}
