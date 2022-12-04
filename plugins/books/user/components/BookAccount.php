<?php

namespace Books\User\Components;

use Books\User\Models\Country;
use Db;
use Exception;
use Flash;
use RainLab\User\Components\Account;
use RainLab\User\Facades\Auth;
use Redirect;
use Request;
use Session;

class BookAccount extends Account
{
    public function componentDetails()
    {
        return [
            'name' => 'Book Account Component',
            'description' => 'Extend rainlab user account component'
        ];
    }

    public function onRegisterProxy()
    {
        return Db::transaction(function () {
            $redirect = $this->onRegister();
            $user = Auth::getUser();
            $user->update(['required_post_register' => 0], ['force' => true]);
            return $redirect;
        });
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
                $user->setUserName();
            });


            return $this->makeRedirection();

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }
}
