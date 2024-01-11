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

    protected $blogPostsCurrentPage = 1;

    protected $videoBlogPostsCurrentPage = 1;

    protected int $perPage = 15;

    protected int $perVideoblogPage = 6;

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
                'reposts' => fn($reposts) => $reposts->with('shareable'),
                'books' => fn($books) => $books->public()->defaultEager()->orderByPivot('sort_order', 'desc')])
            ->withCount(['leftAwards', 'receivedAwards', 'subscriptions'])
            ->find($this->profile->id);

        return array_merge([
            'isLoggedIn' => (bool)$this->authUser,
            'isOwner' => $isOwner,
            'sameAccount' => $sameAccount,
            'hasBooks' => (bool) $this->profile->books?->count(),
            'hasContacts' => !$this->profile->isContactsEmpty(),
            'should_call_fit_profile' => $isOwner && $this->profile->isEmpty(),
            'profile' => $this->profile,
            'books' => $this->profile->books,
            'cycles' => $this->profile->cyclesWithShared()
                ->booksEager()
                ->get(),
            //'posts' => $this->profile->posts()->published()->get(),
            'subscribers' => $this->profile->subscribers->groupBy(fn($i) => $i->books_count ? 'authors' : 'readers'),
            'reposts' => $this->profile?->user?->reposts,
            'received_awards_count' => $this->profile?->received_awards_count,
            'left_awards_count' => $this->profile?->left_awards_count,
            'subscriptions_count' => $this->profile?->subscriptions_count,
        ],
            $this->getAuthorComments(),
            $this->getAuthorBlogPosts(),
            $this->getAuthorVideoBlogPosts(),
        );
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
            'comments_paginator' => $can_see_comments ? CustomPaginator::from(
                $this->profile->leftComments()->orderBy('updated_at', 'desc')->paginate(perPage: $this->perPage, currentPage: $this->commentsCurrentPage())
            ) : collect(),
        ];
    }

    public function getAuthorBlogPosts(): array
    {
        $can_see_blog_posts = $this->profile->canSeeBlogPosts($this->authUser?->profile);

        return [
            'can_see_blog_posts' => $can_see_blog_posts,
            'posts_paginator' => $can_see_blog_posts ? CustomPaginator::from(
                $this->profile->postsThroughProfiler()->published()->orderByDesc('id')->paginate(perPage: $this->perPage, currentPage: $this->blogPostsCurrentPage())
            ) : collect(),
        ];
    }

    public function getAuthorVideoBlogPosts(): array
    {
        $can_see_videoblog_posts  = $this->profile->canSeeVideoBlogPosts($this->authUser?->profile);

        return [
            'can_see_videoblog_posts' => $can_see_videoblog_posts,
            'videoblog_posts_paginator' => $can_see_videoblog_posts ? CustomPaginator::from(
                $this->profile->videoblog_posts()->published()->orderByDesc('id')->paginate(
                    $this->perVideoblogPage,
                    $this->videoBlogPostsCurrentPage()
                )
            ) : collect(),
        ];
    }

    public function onCommentsPage()
    {
        return [
            '#author-comments' => $this->renderPartial('@author-comments-tab', $this->getAuthorComments()),
        ];
    }

    public function onBlogPage()
    {
        return [
            '#author-posts' => $this->renderPartial('@author-blog-tab', $this->getAuthorBlogPosts()),
        ];
    }

    public function onVideoBlogPage()
    {
        return [
            '#author-videoposts' => $this->renderPartial('@author-videoblog-tab', $this->getAuthorVideoBlogPosts()),
        ];
    }

    /**
     * @return array
     */
    public function onShowTabSubscribers(): array
    {
        $isOwner = $this->authUser && $this->profile->is($this->authUser?->profile);

        $this->page['isOwner'] = $isOwner;
        $this->page['subscriptions'] = $this->profile->subscriptions;

        return [
            '#author-tab-subscription' => $this->renderPartial('@author-subscribtions-tab'),
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

    public function blogPostsCurrentPage(): int
    {
        return (int)(post('blog-page') ?? $this->blogPostsCurrentPage);
    }

    public function videoBlogPostsCurrentPage(): int
    {
        return (int)(post('videoblog-page') ?? $this->videoBlogPostsCurrentPage);
    }
}
