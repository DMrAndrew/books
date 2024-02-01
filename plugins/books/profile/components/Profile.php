<?php namespace Books\Profile\Components;

use Cms\Classes\ComponentBase;
use Illuminate\Http\RedirectResponse;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;
use Validator;

/**
 * Profile Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Profile extends ComponentBase
{
    protected ?User $user;

    public function init()
    {
        $this->user = Auth::getUser();
    }

    public function componentDetails()
    {
        return [
            'name' => 'Profile Component',
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

    public function onSwitchProfile()
    {
        $profile = \Books\Profile\Models\Profile::find(post('profile_id'))
            ?? $this->controller->run('404');
        if ($this->user->profiles()->find($profile?->id) && $this->user->profile->id !== $profile->id) {
            $profile->service()->switch();

            return Redirect::refresh();
        }
    }

    public function onCreateProfile(): RedirectResponse
    {
        $this->user->profile->service()->newProfile(post());

        return Redirect::refresh();
    }
}
