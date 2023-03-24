<?php

namespace Books\Profile\Components;

use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Components\Widget;
use Books\Comments\Components\Comments;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;

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
        if (! $this->profile = Profile::query()
            ->find($this->profile_id ?? $this->user?->profile->id)) {
            abort(404);
        }

        $comments = $this->addComponent(Comments::class, 'comments');
        $comments->bindModel($this->profile);
        $comments->bindModelOwner($this->profile);
        $recommend = $this->addComponent(Widget::class, 'recommend');
        $recommend->setUpWidget(WidgetEnum::recommend, short: true);
    }

    public function onRender()
    {
        $this->prepareVals();
    }

    protected function prepareVals()
    {
        $authUser = $this->user;
        $isOwner = (bool) $authUser && $this->profile->is($authUser->profile);
        $sameAccount = (bool) $authUser && $this->profile->user->is($authUser);
        $this->page['isLoggedIn'] = (bool) $authUser;

        $this->page['isOwner'] = $isOwner;
        $this->page['sameAccount'] = $sameAccount;
        $this->page['hasContacts'] = ! $this->profile->isContactsEmpty();
        $this->page['should_call_fit_profile'] = $isOwner && $this->profile->isEmpty();
        $can_see_comments = $this->profile->canSeeCommentFeed($authUser->profile);
        $this->profile = Profile::query()
            ->hasSubscriber($this->user?->profile)
            ->with([
                'banner', 'avatar',
                'subscribers' => fn ($subscribers) => $subscribers->shortPublicEager(),
                'subscriptions' => fn ($subscribers) => $subscribers->shortPublicEager(),
                'books' => fn ($books) => $books->public()->defaultEager()->orderByPivot('sort_order', 'desc'),
                'user.cycles' => fn ($cycles) => $cycles->whereHas('books')->booksEager()])
            ->when($can_see_comments, fn ($p) => $p->with(['comments' => fn ($comments) => $comments->with('commentable')]))
            ->find($this->profile->id);

        $this->page['profile'] = $this->profile;
        $this->page['books'] = $this->profile->books;
        $this->page['cycles'] = $this->profile->user->cycles;
        $this->page['subscribers'] = $this->profile->subscribers->groupBy(fn ($i) => $i->books_count ? 'authors' : 'readers');
        $this->page['subscriptions'] = $this->profile->subscriptions;

        $this->page['can_see_comments'] = $can_see_comments;
        $this->page['comments'] = $can_see_comments ? $this->profile->comments : collect();
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
        $this->user->profile->toggleSubscriptions($this->profile);

        return Redirect::refresh();

        return [
            '#sub-button' => $this->renderPartial('@sub-button', ['sub' => $this->user->profile->hasSubscription($this->profile)]),
        ];
    }
}
