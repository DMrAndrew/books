<?php

namespace Books\Profile\Components;

use Books\FileUploader\Components\ImageUploader;
use Books\Profile\Models\Profile as UserProfile;
use Cms\Classes\ComponentBase;
use Db;
use Event;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Request;
use ValidationException;
use Validator;

class ProfileLC extends ComponentBase
{
    protected User $user;

    /**
     * @return mixed
     */
    public function componentDetails()
    {
        return [
            'name' => 'Profile',
            'description' => 'Details',
        ];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser();
        if ($profile = $this->user?->profile) {
            $component = $this->addComponent(
                ImageUploader::class,
                'avatarUploader',
                [
                    'modelClass' => UserProfile::class,
                    'modelKeyColumn' => 'avatar',
                    'deferredBinding' => false,
                    'imageWidth' => 168,
                    'imageHeight' => 168,
                ]
            );
            $component->bindModel('avatar', $profile);

            $component = $this->addComponent(
                ImageUploader::class,
                'bannerUploader',
                [
                    'modelClass' => UserProfile::class,
                    'modelKeyColumn' => 'banner',
                    'deferredBinding' => false,
                    'imageWidth' => 1152,
                    'imageHeight' => 168,

                ]
            );
            $component->bindModel('banner', $profile);
        }
        $this->page['userdata'] = $this->user;
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     * @throws ValidationException
     */
    public function onUpdateProfile()
    {
        try {
            $profile = $this->user->profile;
            $data = array_diff_assoc(post(), $profile->only($profile->getFillable()));
            $profile->removeValidationRule('username', 'required');
            $profile->addValidationRule('username', 'prohibited');
            $profile->addValidationRule('username_clipboard', 'prohibited');

            $validation = Validator::make(
                $data,
                $profile->rules,
                (array) (new UserProfile())->customMessages
            );
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            $profile->update($validation->validated(), ['force' => true]);
            $this->user->refresh();

            return [
                '#primary-spawn' => $this->renderPartial('@primaryInformation', ['userdata' => $this->user]),
                'secondary-spawn' => $this->renderPartial('@profileSecondaryInformation', ['userdata' => $this->user]),
            ];
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    /**
     * @throws ValidationException
     */
    public function onUpdateUsername()
    {
        try {
            Db::transaction(function () {
                $data = post();
                $data['username_clipboard'] = $data['username'];

                $user = Auth::getUser();
                $profile = $user->profile;
                $rules = $profile->rules;
                $validation = Validator::make(
                    $data,
                    [
                        'username' => $rules['username'],
                        'username_clipboard' => $rules['username_clipboard'],
                        'username_clipboard_comment' => $rules['username_clipboard_comment'],
                    ],
                    (array) (new UserProfile())->customMessages
                );
                if ($validation->fails()) {
                    throw new ValidationException($validation);
                }
                if($profile->isUsernameExists($data['username'])){
                    throw new ValidationException(['username' => 'Псевдоним уже занят.']);
                }
                $profile->update([
                    'username_clipboard' => $data['username'],
                    'username_clipboard_comment' => $data['username_clipboard_comment'] ?? null,
                ], ['force' => true]);
                Event::fire('books.profile.username.modify.requested', [$user]);
            });

            return true;
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    public function onRefreshFiles()
    {
        $this->pageCycle();
    }
}
