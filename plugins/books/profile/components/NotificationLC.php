<?php

namespace Books\Profile\Components;

use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * ProfileNotification Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class NotificationLC extends ComponentBase
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
        $this->page['settings'] = $this->getSettings();
    }

    public function getSettings()
    {
        return $this->user->settings()->notify()->get();
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
        try {
            $this->user->profile->service()->updateSettings(post('options'));
            Flash::success('Данные успешно сохранены');

            return [
                '#lc-notification-form' => $this->renderPartial('@default', ['settings' => $this->getSettings()]),
            ];
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
        }
    }
}
