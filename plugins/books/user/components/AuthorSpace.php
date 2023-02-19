<?php

namespace Books\User\Components;

use Books\Comments\Components\Comments;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

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
            'description' => 'No description provided yet...',
        ];
    }

    public function init()
    {
        $this->profile_id = $this->param('profile_id');
        if (!$this->profile = Profile::find($this->profile_id) ?? Auth::getUser()?->profile) {
            abort(404);
        }
        $comments = $this->addComponent(Comments::class,'comments');
        $comments->bindModel($this->profile);
        $this->prepareVals();
    }

    protected function prepareVals()
    {
        $authUser = Auth::getUser();
        $isOwner = (bool)$authUser && $this->profile->id === $authUser->profile->id;
        $this->page['isLoggedIn'] = (bool)$authUser;
        $this->page['profile'] = $this->profile;
        $this->page['isOwner'] = $isOwner;
        $this->page['hasContacts'] = !$this->profile->isContactsEmpty();
        $this->page['should_call_fit_profile'] = $isOwner && $this->profile->isEmpty();
        $books = $this->profile
            ->booksSortedByAuthorOrder()
            ->public()
            ->defaultEager()
            ->get();
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
