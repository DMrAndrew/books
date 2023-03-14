<?php

namespace Books\User\Components;

use Cookie;
use Db;
use Exception;
use Flash;
use October\Rain\Auth\AuthException;
use RainLab\User\Components\Account;
use RainLab\User\Components\Session;
use RainLab\User\Facades\Auth;
use Redirect;
use Request;
use Response;
use ValidationException;

class BookAccount extends Account
{
    public function componentDetails()
    {
        return [
            'name' => 'Book Account Component',
            'description' => 'Extend rainlab user account component',
        ];
    }

    public function onLogout()
    {
        $this->forgetCookie();

        return (new Session())->onLogout();
    }

    public function forgetCookie()
    {
        Cookie::queue(Cookie::forget(name: 'post_register_accepted'));
        Cookie::queue(Cookie::forget(name: 'adult_agreement_accepted'));
        Cookie::queue(Cookie::forget(name: 'loved_genres'));
        Cookie::queue(Cookie::forget(name: 'unloved_genres'));
    }

    public function onSignin()
    {
        try {
            $redirect = parent::onSignin();
            $this->forgetCookie();

            return $redirect;
        } catch (AuthException $authException) {
            throw new ValidationException(['auth' => $authException->getMessage()]);
        }
    }

    public function should_adult_agreement(): bool
    {
        $user = $this->user();

        return $user && $user->asked_adult_agreement == 0 && ! $user->required_post_register && $user->canSetAdult();
    }

    public function onAdultAgreementSave()
    {
        $action = post('action');
        $val = $action === 'accept' ? 1 : 0;
        $this->user()->update(['see_adult' => $val, 'asked_adult_agreement' => 1]);

        return Redirect::refresh();
    }

    public function onPageLoad()
    {
        $partials = [];
        $cookies = [];

        if ($this->user()?->required_post_register) {
            $partials['#post_register_container'] = $this->renderPartial('auth/postRegisterContainer', ['user' => $this->user()]);
        } else {
            $cookies[] = Cookie::make(name: 'post_register_accepted', value: 1, httpOnly: false);
        }

        if ($this->should_adult_agreement()) {
            $partials['#adult_modal_spawn'] = $this->renderPartial('auth/adult-modal', ['active' => 1]);
        } else {
            $cookies[] = Cookie::make(name: 'adult_agreement_accepted', value: 1, httpOnly: false);
        }

        $response = Response::make($partials);
        collect($cookies)->each(fn ($cookie) => $response->withCookie($cookie));

        return $response;
    }

    public function onRegisterProxy()
    {
        try {
            return Db::transaction(function () {
                $redirect = $this->onRegister();
                $user = Auth::getUser();
                $user->update(['required_post_register' => 0]);
                $this->forgetCookie();

                return $redirect;
            });
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    public function onPostRegister()
    {
        try {
            $user = Auth::getUser();
            $data = array_diff_assoc(post(), $user->only($user->getFillable()));
            $user->addValidationRule('password', 'nullable');
            $user->addValidationRule('password_confirmation', 'nullable');
            $user->removeValidationRule('password', 'required:create');
            $user->removeValidationRule('email', 'required');
            if ($user->username) {
                $user->removeValidationRule('username', 'required');
            }

            Db::transaction(function () use ($user, $data) {
                $user->update(array_merge($data, ['required_post_register' => 0]));
                if ($data['username'] ?? false) {
                    $user->profile->update(['username' => $data['username']]);
                }
            });

            $this->forgetCookie();

            return $this->makeRedirection();
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }
}
