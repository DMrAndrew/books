<?php namespace Books\Blog\Components;

use Books\Blog\Models\Post;
use Books\Comments\Components\Comments;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Blog Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BlogPost extends ComponentBase
{
    protected Post $post;

    public function componentDetails()
    {
        return [
            'name' => 'BlogPost Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->post = Post
            ::query()
            ->slug($this->param('post_slug'))
            ->published()
            ->firstOrFail();

        $authUser = Auth::getUser();
        $can_see_blog_posts = $this->post->profile->canSeeBlogPosts($authUser?->profile);
        if (!$can_see_blog_posts) {
            abort(404);
        }

        $this->page['post'] = $this->post;

        $comments = $this->addComponent(Comments::class, 'comments');
        $comments->bindModel($this->post);
        $comments->bindModelOwner($this->post->profile);
    }
}
