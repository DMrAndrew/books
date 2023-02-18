<?php

namespace Books\Profile\Components;

use Books\User\Classes\UserSettingsEnum;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * ProfilePrivacy Component
 *
 * TODO объединить ProfilePrivacy и ProfileNotification
 */
class PrivacyLC extends ComponentBase
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
        $this->page['settings'] = $this->getSettings();
    }

    public function getSettings()
    {
        return $this->user->settings()->privacy()->get();
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

    public function onUpdate()
    {

        collect(post('options'))->each(function ($option, $key) {
            $this->user->settings()->type(UserSettingsEnum::tryFrom($key))->first()?->update(['value' => $option]);
        });

        return [
            '#profile_privacy_form' => $this->renderPartial('@default', ['settings' => $this->getSettings()]),
        ];
    }
}
