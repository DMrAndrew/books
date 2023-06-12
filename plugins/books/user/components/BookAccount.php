<?php

namespace Books\User\Components;

use ApplicationException;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Components\Widget;
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
use RainLab\User\Models\User as UserModel;
use Redirect;
use Request;
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

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub
        $new = $this->addComponent(Widget::class, 'widget_new');
        $new->setUpWidget(WidgetEnum::new, withAll: true, short: true);

        $interested = $this->addComponent(Widget::class, 'interested');
        $interested->setUpWidget(WidgetEnum::interested, short: true);

        $gainingPopularity = $this->addComponent(Widget::class, 'gainingPopularity');
        $gainingPopularity->setUpWidget(WidgetEnum::gainingPopularity, withAll: true);

        $hot_new = $this->addComponent(Widget::class, 'hotNew');
        $hot_new->setUpWidget(WidgetEnum::hotNew, withAll: true);

        $recommend = $this->addComponent(Widget::class, 'recommend');
        $recommend->setUpWidget(WidgetEnum::recommend, short: true, withAll: true);

        $todayDiscount = $this->addComponent(Widget::class, 'todayDiscount');
        $todayDiscount->setUpWidget(WidgetEnum::todayDiscount, withAll: true);
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
                $rules = (new UserModel)->rules;

                $v = Validator::make(
                    post(),
                    $rules,
                    $this->getValidatorMessages(),
                    $this->getCustomAttributes()
                );

                if ($v->fails()) {
                    throw new ValidationException($v);
                }
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
