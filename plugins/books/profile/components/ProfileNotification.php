<?php namespace Books\Profile\Components;

use Books\User\Classes\SettingsTagEnum;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * ProfileNotification Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class ProfileNotification extends ComponentBase
{
    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'ProfileNotification Component',
            'description' => 'No description provided yet...'
        ];
    }


    public function onRun()
    {
        if ($user = Auth::getUser()) {
            $this->page['settings'] = $this->getNotifySettings();
        }
    }

    function getNotifySettings()
    {
        if ($user = Auth::getUser()) {
            return [
                'profilable' => $user->profileSettings->filter->hasTag(SettingsTagEnum::NOTIFICATION),
                'accountable' => $user->accountSettings->filter->hasTag(SettingsTagEnum::NOTIFICATION),
            ];

        }
        return [];
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

    public function onUpdateNotify()
    {

        if ($user = Auth::getUser()) {
            collect(post('options'))->each(function ($option, $key) use ($user) {
                $user->settings()->updateOrCreate(['setting_id' => $key], ['value' => $option]);
            });
        }

        return [
            'profile/notification' => $this->renderPartial('profile/notification', ['settings' => $this->getNotifySettings()])
        ];

    }
}
