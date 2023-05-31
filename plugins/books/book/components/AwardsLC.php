<?php namespace Books\Book\Components;

use Books\Profile\Models\Profile;
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
    protected ?Profile $profile;

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
        $this->profile = Auth::getUser()?->profile;

    }

    public function onRender()
    {
        $this->page['left'] = $this->profile?->leftAwards()->with('book')->orderByDesc('id')->get();
        $this->page['received'] = $this->profile?->receivedAwards()->with('book', 'profile')->orderByDesc('id')->get();
    }

    public function bindProfile(Profile $profile)
    {
        $this->profile = $profile;
    }
}
