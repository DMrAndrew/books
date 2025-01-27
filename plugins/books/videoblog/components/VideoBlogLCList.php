<?php namespace Books\Videoblog\Components;

use App\classes\CustomPaginator;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * BlogLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class VideoBlogLCList extends ComponentBase
{
    protected Profile $profile;

    protected int $postsCount;

    protected $videoBlogPostsCurrentPage = 1;

    protected int $perVideoblogPage = 6;

    public function componentDetails()
    {
        return [
            'name' => 'VideoBlogLCList Component',
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
                'default' => $this->perVideoblogPage,
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

        $this->postsCount = $this->profile->videoblog_posts()->count();

        $this->prepareVals();
    }

    public function prepareVals()
    {
        $this->page['profile'] = $this->profile;
        $this->page['postsCount'] = $this->postsCount;
        $this->page['posts'] = CustomPaginator::from($this->getPosts());
    }

    public function onVideoBlogPage()
    {
        return [
            '#videoposts' => $this->renderPartial('@list', ['post' => $this->getPosts()]),
        ];
    }
    public function videoBlogPostsCurrentPage(): int
    {
        return (int)(post('videoblog-lc') ?? $this->videoBlogPostsCurrentPage);
    }

    protected function getPosts(): CustomPaginator
    {
        return CustomPaginator::from($this->profile->videoblog_posts()->orderByDesc('id')->paginate(
            $this->perVideoblogPage,
            $this->videoBlogPostsCurrentPage()
        )
        );
    }
}
