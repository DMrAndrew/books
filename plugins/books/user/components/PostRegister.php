<?php

namespace Books\User\Components;

use Db;
use Flash;
use Request;
use Exception;
use RainLab\User\Facades\Auth;
use Books\User\Models\Country;
use RainLab\User\Components\Account;

class PostRegister extends Account
{
    public function componentDetails()
    {
        return [
            'name' => 'Расширение ' ./*Account*/ 'rainlab.user::lang.account.account',
            'description' => 'Расширение регистрации для ' . /*User management form.*/ 'rainlab.user::lang.account.account_desc'
        ];
    }

    public function onPostRegister()
    {
        try {
            $user = Auth::getUser();
            $data = array_diff(post(), $user->only($user->getFillable()));
            $user->addValidationRule('password', 'nullable');
            $user->removeValidationRule('password', 'required:create');
            $user->removeValidationRule('email', 'required');
            if ($user->username) {
                $user->removeValidationRule('username', 'required');
            }
            if (!isset($data['country_id'])) {
                $data['country_id'] = Country::code('ru')->first()->id;
            }
            Db::transaction(function () use ($user, $data) {
                $user->update(array_merge($data, ['required_post_register' => 0]));
                $user->setUserName();
            });

            return $this->makeRedirection();

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }
}
