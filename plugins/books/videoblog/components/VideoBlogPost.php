<?php namespace Books\Videoblog\Components;

use Books\Blog\Models\Post;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Components\Widget;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Books\Comments\Components\Comments;
use Books\Videoblog\Models\Videoblog;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * Blog Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class VideoBlogPost extends ComponentBase
{
    protected Videoblog $post;

    public function componentDetails()
    {
        return [
            'name' => 'VideoBlogPost Component',
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
                'title' => 'VideoBlog post slug',
                'description' => 'Уникальный код публикации',
            ],
        ];
    }

    public function init()
    {
        $this->post = Videoblog::slug($this->property('slug'))
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
        $can_see_videoblog_posts = $this->post->profile->canSeeVideoBlogPosts($profile);
        if (!$can_see_videoblog_posts) {
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
        $manager->register('videoblogpost', static function (BreadcrumbsGenerator $trail, $params) use ($post) {
            $trail->parent('home');
            $trail->push($post->profile->username, '/author-page/' . $post->profile->id);
            $trail->push('Видеоблог', '/author-page/' . $post->profile->id);
            $trail->push($post->title);
        });
    }
}
