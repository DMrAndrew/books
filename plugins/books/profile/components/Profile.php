<?php namespace Books\Profile\Components;

use Flash;
use Request;
use Exception;
use Validator;
use ValidationException;
use Cms\Classes\ComponentBase;
use Books\User\Models\Country;
use RainLab\User\Facades\Auth;
use Books\FileUploader\Components\ImageUploader;
use Books\Profile\Models\Profile as UserProfile;

class Profile extends ComponentBase
{

    /**
     * @return mixed
     */
    public function componentDetails()
    {
        return [
            'name' => 'Profile',
            'description' => 'Details'
        ];
    }

    public function init()
    {

        $component = $this->addComponent(
            ImageUploader::class,
            'avatarUploader',
            [
                'modelClass' => UserProfile::class,
                'modelKeyColumn' => 'avatar',
                'deferredBinding' => false,
                'imageWidth' => 150
            ]
        );

        $component->bindModel('avatar', Auth::getUser()->profile);

        $component = $this->addComponent(
            ImageUploader::class,
            'bannerUploader',
            [
                'modelClass' => UserProfile::class,
                'modelKeyColumn' => 'banner',
                'deferredBinding' => false,
                'imageWidth' => 250,
                'imageHeight' => 150
            ]
        );

        $component->bindModel('banner', Auth::getUser()->profile);
    }

    public function onRefreshFiles()
    {
        $this->pageCycle();
    }

    public function onRun()
    {
        $this->prepareVars();
    }

    public function prepareVars()
    {
        $this->page['userdata'] = $this->userProfile();
    }


    public function userProfile()
    {
        $user = Auth::getUser();
        $user->load([
            'profile.avatar',
            'profile.banner',
        ]);
        return $user;
    }

    public function countries()
    {
        return Country::all();
    }


    public function onUpdatePrimaryData()
    {
        try {
            $user = Auth::getUser();
            $profile = $user->profile;
            $data = array_diff_assoc(post(), $profile->only($profile->getFillable()));
            $profile->removeValidationRule('username', 'required');
            $validation = Validator::make(
                $data,
                $profile->rules,
                (array)(new UserProfile())->customMessages
            );
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
            $data = $validation->validated();

            $profile->update($data);

            $userdata = array_diff_assoc(post(), $user->only($user->getFillable()));
            $userdata['show_birthday'] = !!($userdata['show_birthday'] ?? false);


            $user->update($userdata);

            return [
                'profile/primaryInformation' => $this->renderPartial('profile/primaryInformation', ['userdata' => $this->userProfile()])
            ];
        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    public function onUpdateSecondaryData()
    {
        try {
            $user = Auth::getUser();
            $profile = $user->profile;
            $data = array_diff_assoc(post(), $profile->only($profile->getFillable()));
            $profile->update($data);
            return [
                'profile/profileSecondaryInformation' => $this->renderPartial('profile/profileSecondaryInformation', ['userdata' => $this->userProfile()])
            ];
        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }

    }

    public function onUpdateUsername()
    {
        try {
            $data = post();
            $data['username_clipboard'] = $data['username'];
            $user = Auth::getUser();
            $profile = $user->profile;

            $validation = Validator::make(
                $data,
                [
                    'username' => $profile->rules['username'],
                    'username_clipboard' => $profile->rules['username_clipboard']
                ],
                (array)(new UserProfile())->customMessages
            );
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
            $profile->update(['username_clipboard' => $data['username']]);

            //TODO fire event, notify admin
            return true;
        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }

    }
}
