<?php namespace Books\Profile\Components;

use Books\User\Classes\SettingsTagEnum;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * ProfilePrivacy Component
 *
 * TODO объединить ProfilePrivacy и ProfileNotification
 */
class ProfilePrivacy extends ComponentBase
{
    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'ProfilePrivacy Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function onRun()
    {
        $this->page['settings'] = $this->getPrivactSettings();
    }

    function getPrivactSettings()
    {
        if ($user = Auth::getUser()) {
            return $user->profileSettings->filter->hasTag(SettingsTagEnum::PRIVACY);
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
    public function onUpdatePrivacy()
    {

        if ($user = Auth::getUser()) {
            collect(post('options'))->each(function ($option, $key) use ($user) {
                $user->profileSettings()->updateOrCreate(['setting_id' => $key], ['value' => $option]);
            });
        }

        return [
            '#profile_privacy_form' => $this->renderPartial('profile/privacy', ['settings' => $this->getPrivactSettings()])
        ];

    }
}
