<?php

namespace Books\Profile\Components;

use App\classes\CustomPaginator;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Components\AwardsLC;
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
        $this->profile_id = (int)$this->param('profile_id');
        $this->authUser = Auth::getUser();
        $this->profile = Profile::query()->find($this->profile_id ?? $this->authUser?->profile->id) ?? abort(404);

        $comments = $this->addComponent(Comments::class, 'comments');
        $comments->bindModel($this->profile);
        $comments->bindModelOwner($this->profile);
        $recommend = $this->addComponent(Widget::class, 'recommend');
        $recommend->setUpWidget(WidgetEnum::recommend, short: true);
        $awards = $this->addComponent(AwardsLC::class, 'awardsLC');
        $awards->bindProfile($this->profile);
    }

    public function onRender()
    {
        foreach ($this->prepareVals() as $key => $val) {
            $this->page[$key] = $val;
        }
    }

    protected function prepareVals()
    {
        $isOwner = $this->authUser && $this->profile->is($this->authUser?->profile);
        $sameAccount = $this->authUser && $this->profile && $this->profile->user?->is($this->authUser);

        $this->profile = Profile::query()
            ->hasSubscriber($this->authUser?->profile)
            ->with([
                'banner', 'avatar',
                'subscribers' => fn($subscribers) => $subscribers->shortPublicEager(),
                'subscriptions' => fn($subscribers) => $subscribers->shortPublicEager(),
                'reposts' => fn($reposts) => $reposts->with('shareable'),
                'books' => fn($books) => $books->public()->defaultEager()->orderByPivot('sort_order', 'desc')])
            ->withCount(['leftAwards', 'receivedAwards'])
            ->find($this->profile->id);

        return array_merge([
            'isLoggedIn' => (bool)$this->authUser,
            'isOwner' => $isOwner,
            'sameAccount' => $sameAccount,
            'hasContacts' => !$this->profile->isContactsEmpty(),
            'should_call_fit_profile' => $isOwner && $this->profile->isEmpty(),
            'profile' => $this->profile,
            'books' => $this->profile->books,
            'cycles' => $this->profile->cycles()->load(['books' => fn($books) => $books->public()])->filter(fn($i) => $i->books->count())->values(),
            'subscribers' => $this->profile->subscribers->groupBy(fn($i) => $i->books_count ? 'authors' : 'readers'),
            'subscriptions' => $this->profile->subscriptions,
            'reposts' => $this->profile?->user?->reposts,
            'received_awards_count' => $this->profile?->received_awards_count,
            'left_awards_count' => $this->profile?->left_awards_count,
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
        $can_see_comments = $this->profile->canSeeCommentFeed($this->authUser?->profile);

        return [
            'can_see_comments' => $can_see_comments,
            'comments_paginator' => $can_see_comments ? CustomPaginator::fromLengthAwarePaginator(
                $this->profile->leftComments()->orderBy('updated_at', 'desc')->paginate(perPage: $this->perPage, currentPage: $this->commentsCurrentPage())
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
        $this->authUser?->profile->toggleSubscriptions($this->profile);

        return Redirect::refresh();
    }

    public function commentsCurrentPage(): int
    {
        return (int)(post('page') ?? $this->commentsCurrentPage);
    }
}
