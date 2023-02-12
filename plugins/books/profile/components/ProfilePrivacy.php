<?php

namespace Books\Profile\Components;

use Books\User\Classes\SettingsTagEnum;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * ProfilePrivacy Component
 *
 * TODO объединить ProfilePrivacy и ProfileNotification
 */
class ProfilePrivacy extends ComponentBase
{
    protected User $user;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'ProfilePrivacy Component',
            'description' => 'No description provided yet...',
        ];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser();
        $this->page['settings'] = $this->getPrivactSettings();
    }

    public function getPrivactSettings()
    {
        return $this->user->profileSettings->filter->hasTag(SettingsTagEnum::PRIVACY);
    }

    /**
     * defineProperties for the component
     *
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function onUpdatePrivacy()
    {
        collect(post('options'))->each(function ($option, $key) {
            $this->user->profileSettings()->updateOrCreate(['setting_id' => $key], ['value' => $option]);
            $this->user->refresh();
        });

        return [
            '#profile_privacy_form' => $this->renderPartial('profile/privacy', ['settings' => $this->getPrivactSettings()]),
        ];
    }
}
