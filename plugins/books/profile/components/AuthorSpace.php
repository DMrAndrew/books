<?php

namespace Books\Profile\Components;

use App\classes\CustomPaginator;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Components\AwardsLC;
use Books\Book\Components\SaleTagBlock;
use Books\Book\Components\Widget;
use Books\Comments\Components\Comments;
use Books\Profile\Models\Profile;
use Cms\Classes\CmsException;
use Cms\Classes\ComponentBase;
use Illuminate\Pagination\Paginator;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;
use Request;

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
    protected int $perPageBooks = 10;
    protected int $perPageBlogPosts = 10;
    protected int $perPageVideoblogPosts = 6;
    protected int $perPageComments = 20;

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
        $this->profile = Profile::query()->find($this->profile_id ?? $this->authUser?->profile->id)
            ?? $this->controller->run('404');

        $comments = $this->addComponent(Comments::class, 'comments');
        $comments->bindModel($this->profile);
        $comments->bindModelOwner($this->profile);
        $recommend = $this->addComponent(Widget::class, 'recommend');
        $recommend->setUpWidget(WidgetEnum::recommend, short: true);
        $awards = $this->addComponent(AwardsLC::class, 'awardsLC');
        $awards->bindProfile($this->profile);
        $this->addComponent(SaleTagBlock::class, 'SaleTagBlock');

        $this->setSEO();
    }

    public function onRender()
    {
        foreach ($this->prepareVals() as $key => $val) {
            $this->page[$key] = $val;
        }

        $this->page['title'] = sprintf(
            '%s - скачать в fb2, epub, txt, pdf или читать онлайн бесплатно',
            $this->profile->username
        );
        //dd($this->page);
    }

    protected function prepareVals()
    {
        $isOwner = $this->authUser && $this->profile->is($this->authUser?->profile);
        $sameAccount = $this->authUser && $this->profile && $this->profile->user?->is($this->authUser);

        $this->profile = Profile::query()
            ->hasSubscriber($this->authUser?->profile)
            ->with([
                'banner',
                'avatar'
                //'books' => fn($books) => $books->public()->defaultEager()->orderByPivot('sort_order', 'desc')
            ])
            ->withCount(['leftAwards', 'receivedAwards', 'subscriptions', 'subscribers', 'reposts'])
            ->find($this->profile->id);

        return array_merge([
            'isLoggedIn' => (bool)$this->authUser,
            'isOwner' => $isOwner,
            'sameAccount' => $sameAccount,
            'hasContacts' => !$this->profile->isContactsEmpty(),
            'should_call_fit_profile' => $isOwner && $this->profile->isEmpty(),
            'profile' => $this->profile,
            'hasBooks' => (bool) $this->profile->books?->count(),
            'cycles' => $this->profile->cyclesWithShared()
                ->booksEager()
                ->get(),

            'received_awards_count' => $this->profile?->received_awards_count,
            'left_awards_count' => $this->profile?->left_awards_count,
            'subscriptions_count' => $this->profile?->subscriptions_count,
            'subscribers_count' => $this->profile?->subscribers_count,
            'reposts_count' => $this->profile?->reposts_count,
        ],
            $this->getAuthorBooks(),
            $this->getAuthorCommentsCount(),
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

    /**
     * @return array
     */
    public function getAuthorBooks(): array
    {
        return [
            'books_paginator' => CustomPaginator::from(
                $this->profile->books()->defaultEager()
                    ->orderBy('updated_at', 'desc')
                    ->paginate(
                        perPage: $this->perPageBooks,
                        pageName: 'booksPage',
                        currentPage: $this->booksCurrentPage(),
                    )
            ),
        ];
    }

    public function getAuthorCommentsCount(): array
    {
        $can_see_comments = $this->profile->canSeeCommentFeed($this->authUser?->profile);

        return [
            'can_see_comments' => $can_see_comments,
            'comments_count' => $can_see_comments ? $this->profile->leftComments()->count() : 0,
        ];
    }

    /**
     * @return array
     */
    public function getAuthorComments(): array
    {
        $can_see_comments = $this->profile->canSeeCommentFeed($this->authUser?->profile);

        return [
            'can_see_comments' => $can_see_comments,
            'comments_paginator' => $can_see_comments ? CustomPaginator::from(
                $this->profile
                    ->leftComments()
                    ->orderBy('updated_at', 'desc')
                    ->paginate(
                        perPage: $this->perPageComments,
                        pageName: 'commentsPage',
                        currentPage: $this->commentsCurrentPage(),
                    )
            ) : collect(),
        ];
    }

    /**
     * @return array
     */
    public function getAuthorBlogPosts(): array
    {
        $can_see_blog_posts = $this->profile->canSeeBlogPosts($this->authUser?->profile);

        return [
            'can_see_blog_posts' => $can_see_blog_posts,
            'posts_paginator' => $can_see_blog_posts ? CustomPaginator::from(
                $this->profile->postsThroughProfiler()->published()->orderByDesc('id')
                    ->paginate(
                        perPage: $this->perPageBlogPosts,
                        pageName: 'blogPostsPage',
                        currentPage: $this->blogPostsCurrentPage()
                    )
            ) : collect(),
        ];
    }

    /**
     * @return array
     */
    public function getAuthorVideoBlogPosts(): array
    {
        $can_see_videoblog_posts  = $this->profile->canSeeVideoBlogPosts($this->authUser?->profile);

        return [
            'can_see_videoblog_posts' => $can_see_videoblog_posts,
            'videoblog_posts_paginator' => $can_see_videoblog_posts ? CustomPaginator::from(
                $this->profile->videoblog_posts()->published()->orderByDesc('id')
                    ->paginate(
                        perPage: $this->perPageVideoblogPosts,
                        pageName: 'videoblogPostsPage',
                    )
            ) : collect(),
        ];
    }

    /**
     * @return array
     */
    public function onBooksPage()
    {
        return [
            '#author-books' => $this->renderPartial('@author-books-tab', $this->getAuthorBooks()),
        ];
    }

    public function onCommentsPage()
    {
        return [
            '#author-tab-comments' => $this->renderPartial('@author-comments-tab', $this->getAuthorComments()),
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
    public function onShowTabSubscribtions(): array
    {
        $isOwner = $this->authUser && $this->profile->is($this->authUser?->profile);

        $this->page['isOwner'] = $isOwner;
        $this->page['subscriptions'] = $this->profile->subscriptions;

        return [
            '#author-tab-subscriptions' => $this->renderPartial('@author-subscribtions-tab'),
        ];
    }

    /**
     * @return array
     */
    public function onShowTabSubscribers(): array
    {
        $isOwner = $this->authUser && $this->profile->is($this->authUser?->profile);

        $this->page['isOwner'] = $isOwner;
        $this->page['subscribers'] = $this->profile
            ->subscribers()->shortPublicEager()->get()
            ->groupBy(fn($i) => $i->books_count ? 'authors' : 'readers');

        return [
            '#author-tab-subscribers' => $this->renderPartial('@author-subscribers-tab'),
        ];
    }

    /**
     * @return array
     * @throws CmsException
     */
    public function onShowTabAwards(): array
    {
        $awards = $this->addComponent(AwardsLC::class, 'awardsLC');
        $awards->bindProfile($this->profile);

        return [
            '#author-tab-awards' => $this->renderPartial('@author-awards-tab'),
        ];
    }

    /**
     * @return array
     */
    public function onShowTabReposts(): array
    {
        $this->page['profile'] = $this->profile;
        $this->page['reposts'] = $this->profile?->user?->reposts;

        return [
            '#author-tab-reposts' => $this->renderPartial('@author-reposts-tab'),
        ];
    }

    /**
     * @return array
     */
    public function onShowTabComments(): array
    {
        return [
            '#author-tab-comments' => $this->renderPartial('@author-comments-tab', $this->getAuthorComments()),
        ];
    }

    public function onToggleSubscribe()
    {
        $this->authUser?->profile->toggleSubscriptions($this->profile);

        return Redirect::refresh();
    }

    public function booksCurrentPage(): int
    {
        return (int)(post('booksPage') ?? get('booksPage') ?? $this->commentsCurrentPage);
    }

    public function commentsCurrentPage(): int
    {
        return (int)(post('commentsPage') ?? get('commentsPage') ?? $this->commentsCurrentPage);
    }

    public function blogPostsCurrentPage(): int
    {
        return (int)(post('blogPostsPage') ?? (int)get('blogPostsPage') ?? $this->blogPostsCurrentPage);
    }

    public function videoBlogPostsCurrentPage(): int
    {
        return (int)(post('videoblogPostsPage') ?? (int)get('blogPostsPage') ?? $this->videoBlogPostsCurrentPage);
    }

    private function setSEO(): void
    {
        $this->page->og_type = 'profile';
        $this->page->meta_canonical = Request::url();

        $this->page->meta_title = sprintf(
            '%s - скачать в fb2, epub, txt, pdf или читать онлайн бесплатно',
            $this->profile->username
        );
        $this->page->meta_description = sprintf(
            'Карточка автора %s - все произведения доступные в нашей электронной библиотеке',
            $this->profile->username
        );
    }
}
