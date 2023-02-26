<?php namespace Books\User\Components;

use Books\User\Classes\UserService;
use Cms\Classes\ComponentBase;
use Country;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;

/**
 * UserSettingsLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class UserSettingsLC extends ComponentBase
{
    protected User $user;
    protected UserService $service;

    public function componentDetails()
    {
        return [
            'name' => 'UserSettingsLC Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        if ($r = redirectIfUnauthorized()) {
            return $r;
        }
        $this->user = Auth::getUser();
        $this->service = new UserService($this->user);
    }

    public function onRun()
    {
        $this->vals();
    }

    public function vals()
    {
        $this->page['user'] = $this->user;
        $this->page['countries'] = Country::query()->isEnabled()->get();
    }

    public function onUpdateCommon()
    {
        try {
            $this->service->update(post());
            Flash::success('Данные успешно сохранены');
            return Redirect::refresh();

        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
        }
    }

    public function onChangePassword()
    {
    }

    public function onForgetPassword()
    {
    }
}
