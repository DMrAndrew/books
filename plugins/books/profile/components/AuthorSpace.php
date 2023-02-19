<?php

namespace Books\Profile\Components;

use Books\Comments\Components\Comments;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * AuthorSpace Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class AuthorSpace extends ComponentBase
{
    protected ?int $profile_id = null;

    protected ?Profile $profile;
    protected ?User $user;

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
        $this->user = Auth::getUser();
        if (!$this->profile = Profile::query()
            ->hasSubscriber($this->user->profile)
            ->find($this->profile_id ?? $this->user?->profile->id)) {
            abort(404);
        }

        $comments = $this->addComponent(Comments::class, 'comments');
        $comments->bindModel($this->profile);
        $comments->bindModelOwner($this->profile);
    }

    public function onRender()
    {
        $this->prepareVals();
    }

    protected function prepareVals()
    {
        $authUser = $this->user;
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

    public function onToggleSubscribe()
    {
        $this->profile->toggleSubscribe($this->user->profile);
        return [
            '#sub-button' => $this->renderPartial('@sub-button', ['sub' => $this->profile->hasSubscriber($this->user->profile)])
        ];
    }
}