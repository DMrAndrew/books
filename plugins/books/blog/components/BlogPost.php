<?php namespace Books\Blog\Components;

use Books\Blog\Models\Post;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Components\Widget;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Books\Comments\Components\Comments;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

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
        return [
            'slug' => [
                'title' => 'Blog post slug',
                'description' => 'Уникальный код публикации',
            ],
        ];
    }

    public function init()
    {
        $this->post = Post::slug($this->property('slug'))
            ->published()
            ->firstOrFail();
        $comments = $this->addComponent(Comments::class, 'comments');
        $comments->bindModel($this->post);
        $comments->bindModelOwner($this->post->profile);
        $this->registerBreadcrumbs();

    }

    public function onRun()
    {
        $profile = Auth::check() ? Auth::getUser()->profile : null;
        $can_see_blog_posts = $this->post->profile->canSeeBlogPosts($profile);
        if (!$can_see_blog_posts) {
            $this->page['privacyRestricted'] = true;
        }

        $this->page['post'] = $this->post;



        $recommend = $this->addComponent(Widget::class, 'recommend');
        $recommend->setUpWidget(WidgetEnum::recommend, short: true);
    }

    /**
     * @return void
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $post = $this->post;
        $manager->register('blogpost', static function (BreadcrumbsGenerator $trail, $params) use ($post) {
            $trail->parent('home');
            $trail->push($post->profile->username, '/author-page/' . $post->profile->id);
            $trail->push('Блог', '/author-page/' . $post->profile->id);
            $trail->push($post->title);
        });
    }
}
