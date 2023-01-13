<?php namespace Books\User\Components;

use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use Books\Profile\Models\Profile;

/**
 * AuthorSpace Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class AuthorSpace extends ComponentBase
{

    protected ?int $profile_id = null;
    protected ?Profile $profile;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'AuthorSpace Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function init()
    {
        $this->profile_id = $this->param('profile_id');
        if (!$this->profile = Profile::find($this->profile_id) ?? Auth::getUser()?->profile) {
            abort(404);
        }
        $this->prepareVals();
    }

    protected function prepareVals()
    {
        $authorized = Auth::getUser();
        $props = $this->profile;
        $this->page['isLogging'] = !!$authorized;
        $this->page['user'] = $this->profile->user;
        $this->page['profile'] = $this->profile;
        $this->page['isOwner'] = !!$authorized && $this->profile->id === $authorized->profile->id;
        $this->page['hasContacts'] = collect($props->only(['ok', 'phone', 'tg', 'vk', 'email', 'website',]))->some(fn($i) => !!$i);
        $this->page['should_call_fit_profile'] = $authorized && !collect($props->only(['avatar', 'banner', 'status', 'about']))->some(fn($i) => !!$i);
        $books = $this->profile->publicBooks()->get();
        $books->load('user');
        $this->page['books'] = $books;
        $this->page['books_count'] = $books->count();
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
}
