<?php namespace Books\Profile\Components;

use Exception;
use Flash;
use Request;
use ValidationException;
use Validator;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
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

    public function onProfileUpdate()
    {
        try {
            $user = Auth::getUser();
            $profile = $user->profile;
            $data = array_diff(post(), $profile->only($profile->getFillable()));
            $profile->removeValidationRule('username','required');
            $validation = Validator::make(
                $data,
                $profile->rules,
                (array)(new UserProfile())->customMessages
            );
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
            $data = $validation->validated();
            if ($data['username'] ?? false) {
                $data['username_clipboard'] = $data['username'];
                unset($data['username']);
            }
           return $profile->update($data);
        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }

    }
}
