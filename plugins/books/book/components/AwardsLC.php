<?php namespace Books\Book\Components;

use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * AwardsLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class AwardsLC extends ComponentBase
{
    protected User $user;

    public function componentDetails()
    {
        return [
            'name' => 'AwardsLC Component',
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
        $this->page['left'] = $this->user->profile->leftAwards()->with('book')->orderByDesc('id')->get();
        $this->page['received'] = $this->user->profile->receivedAwards()->with('book', 'profile')->orderByDesc('id')->get();
    }
}
