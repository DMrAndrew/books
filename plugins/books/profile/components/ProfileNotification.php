<?php

namespace Books\Profile\Components;

use Books\User\Classes\SettingsTagEnum;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * ProfileNotification Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class ProfileNotification extends ComponentBase
{
    protected User $user;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'ProfileNotification Component',
            'description' => 'No description provided yet...',
        ];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser();
        $this->page['settings'] = $this->getNotifySettings();
    }

    public function getNotifySettings()
    {
        return [
            'profilable' => $this->user->profileSettings->filter->hasTag(SettingsTagEnum::NOTIFICATION),
            'accountable' => $this->user->accountSettings->filter->hasTag(SettingsTagEnum::NOTIFICATION),
        ];
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
        collect(post('options'))->each(function ($option, $key) {
            $this->user->settings()->updateOrCreate(['setting_id' => $key], ['value' => $option]);
            $this->user->refresh();
        });

        return [
            'profile/notification' => $this->renderPartial('profile/notification', ['settings' => $this->getNotifySettings()]),
        ];
    }
}
