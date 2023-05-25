<?php namespace Books\Reposts\Components;

use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * RepostsLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class RepostsLC extends ComponentBase
{
    protected User $user;

    public function componentDetails()
    {
        return [
            'name' => 'RepostsLC Component',
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
        $this->page['reposts'] = $this->user->profile->reposts()->with('shareable')->get();
    }

}
