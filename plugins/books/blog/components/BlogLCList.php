<?php namespace Books\Blog\Components;

use App\classes\CustomPaginator;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * BlogLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BlogLCList extends ComponentBase
{
    protected Profile $profile;
    protected int $postsCount;
    private int $blogPostsCurrentPage = 1;


    public function componentDetails()
    {
        return [
            'name' => 'BlogLCList Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [
            'recordsPerPage' => [
                'title' => 'Публикаций на странице',
                'comment' => 'Количество публикаций отображаемых на одной странице',
                'default' => 2,
            ],
        ];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $user = Auth::getUser();
        $this->profile = $user->profile;

        $this->postsCount = $this->profile->posts()->count();

        $this->prepareVals();
    }

    public function prepareVals()
    {
        $this->page['profile'] = $this->profile;
        $this->page['postsCount'] = $this->postsCount;
        $this->page['posts'] = $this->getPosts();
    }

    public function onBlogPage()
    {
        return [
            '#posts' => $this->renderPartial('@list', ['post' => $this->getPosts()]),
        ];
    }
    public function blogPostsCurrentPage(): int
    {
        return (int)(post('blog-lc') ?? $this->blogPostsCurrentPage);
    }

    protected function getPosts(): CustomPaginator
    {
        return CustomPaginator::from($this->profile->posts()->orderByDesc('id')->paginate(
            (int) $this->property('recordsPerPage', 16),
            $this->blogPostsCurrentPage()
        )
        );
    }
}
