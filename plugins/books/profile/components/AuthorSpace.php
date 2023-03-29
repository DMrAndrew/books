<?php

namespace Books\Profile\Components;

use App\classes\CustomPaginator;
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

    protected ?User $authUser;

    protected $commentsCurrentPage = 1;

    protected int $perPage = 15;

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
        $this->authUser = Auth::getUser();
        if (! $this->profile = Profile::query()
            ->find($this->profile_id ?? $this->authUser?->profile->id)) {
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
        foreach ($this->prepareVals() as $key => $val) {
            $this->page[$key] = $val;
        }
    }

    protected function prepareVals()
    {
        $isOwner = $this->authUser && $this->profile->is($this->authUser->profile);
        $sameAccount = $this->authUser && $this->profile->user->is($this->authUser);

        $this->profile = Profile::query()
            ->hasSubscriber($this->authUser?->profile)
            ->with([
                'banner', 'avatar',
                'subscribers' => fn ($subscribers) => $subscribers->shortPublicEager(),
                'subscriptions' => fn ($subscribers) => $subscribers->shortPublicEager(),
                'books' => fn ($books) => $books->public()->defaultEager()->orderByPivot('sort_order', 'desc'),
                'cycles' => fn ($cycles) => $cycles->whereHas('books', fn ($books) => $books->public())->booksEager()])
            ->find($this->profile->id);

        return array_merge([
            'isLoggedIn' => (bool) $this->authUser,
            'isOwner' => $isOwner,
            'sameAccount' => $sameAccount,
            'hasContacts' => ! $this->profile->isContactsEmpty(),
            'should_call_fit_profile' => $isOwner && $this->profile->isEmpty(),
            'profile' => $this->profile,
            'books' => $this->profile->books,
            'cycles' => $this->profile->cycles,
            'subscribers' => $this->profile->subscribers->groupBy(fn ($i) => $i->books_count ? 'authors' : 'readers'),
            'subscriptions' => $this->profile->subscriptions,
        ], $this->getAuthorComments());
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

    public function getAuthorComments(): array
    {
        $can_see_comments = $this->profile->canSeeCommentFeed($this->authUser->profile);

        return [
            'can_see_comments' => $can_see_comments,
            'comments_paginator' => $can_see_comments ? CustomPaginator::fromLengthAwarePaginator(
                $this->profile->user->comments()->with('commentable')->orderBy('updated_at', 'desc')->paginate(perPage: $this->perPage, page: $this->commentsCurrentPage())
            ) : collect(),
        ];
    }

    public function onCommentsPage()
    {
        return [
            '#author-comments' => $this->renderPartial('@author-comments-tab', $this->getAuthorComments()),
        ];
    }

    public function onToggleSubscribe()
    {
        $this->authUser->profile->toggleSubscriptions($this->profile);

        return Redirect::refresh();

        return [
            '#sub-button' => $this->renderPartial('@sub-button', ['sub' => $this->authUser->profile->hasSubscription($this->profile)]),
        ];
    }

    public function commentsCurrentPage(): int
    {
        return (int) (post('page') ?? $this->commentsCurrentPage);
    }
}
