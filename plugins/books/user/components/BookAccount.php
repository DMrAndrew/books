<?php

namespace Books\User\Components;

use Books\User\Classes\CookieEnum;
use Books\User\Classes\UserService;
use Books\User\Models\TempAdultPass;
use Cookie;
use Db;
use Exception;
use Flash;
use Log;
use October\Rain\Auth\AuthException;
use RainLab\User\Components\Account;
use RainLab\User\Components\Session;
use RainLab\User\Facades\Auth;
use Redirect;
use Request;
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
        return (new Session())->onLogout();
    }

    public function onSignin()
    {
        try {
            return parent::onSignin();
        } catch (AuthException $authException) {
            throw new ValidationException(['auth' => trans('rainlab.user::lang.account.invalid_user')]);
        }
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

    public function onFetch(): array
    {
        if ($this->user()?->required_post_register) {
            return [
                '#post_register_container' => $this->renderPartial('auth/postRegisterContainer', ['user' => $this->user()]),
            ];
        }

        if ($this->user()?->requiredAskAdult()) {
            return ['#adult_modal_spawn' => $this->renderPartial('auth/adult-modal', ['active' => 1])];
        }

        return [];
    }

    public function onRegisterProxy()
    {
        try {
            return Db::transaction(function () {
                $redirect = $this->onRegister();
                $user = Auth::getUser();
                $user?->update(['required_post_register' => 0]);

                return $redirect;
            });
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
            Log::error($ex);
            return [];
        }
    }

    public function onPostRegister()
    {
        try {
            $user = Auth::getUser();
            $data = post();
            $user->addValidationRule('password', 'nullable');
            $user->addValidationRule('password_confirmation', 'nullable');
            $user->removeValidationRule('password', 'required:create');
            $user->removeValidationRule('email', 'required');
            $user->addValidationRule('nickname', 'required');

            Db::transaction(function () use ($user, $data) {
                $user->update(array_merge($data, ['required_post_register' => 0]));
                $user->profile->update(['username' => $data['nickname']]);
            });
            if($user->is_activated){
                Auth::login($user, $this->useRememberLogin());
                Flash::success('Вы успешно зарегистрировались');
            }
            else{
                Auth::logout();
                Flash::success('Данные успешно сохранены. Активируйте аккаунт через указанную почту для входа в учётную запись.');
            }
            return $this->makeRedirection(true);
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    /**
     * @param array $attributes
     * @param array|string $keys
     * @return array
     */
    private function trimStrings(array $attributes, array|string $keys): array
    {
        if (is_string($keys)) {
            $keys = [$keys];
        }

        foreach ($attributes as $key => $value) {
            if (!is_string($value) || !in_array($key, $keys, true)) {
                continue;
            }

            $attributes[$key] = preg_replace('/\s/', '', $value);
        }

        return $attributes;
    }
}
