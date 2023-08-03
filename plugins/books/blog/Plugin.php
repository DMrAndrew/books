<?php namespace Books\Blog;

use Backend;
use Books\Blog\Behaviors\HasBlog;
use Books\Blog\Components\BlogList;
use Books\Blog\Components\BlogPost;
use Books\Blog\Components\BlogLC;
use Books\Blog\Components\BlogLCList;
use Books\Blog\Components\BlogPostCard;
use Books\Profile\Models\Profile;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Blog',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-leaf'
        ];
    }

    /**
     * register method, called when the plugin is first registered.
     */
    public function register()
    {
        //
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        Profile::extend(function (Profile $model) {
            $model->implementClassWith(HasBlog::class);
        });
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            BlogPost::class => 'BlogPost',
            BlogPostCard::class => 'BlogPostCard',
            BlogList::class => 'BlogList',
            BlogLC::class => 'BlogLC',
            BlogLCList::class => 'BlogLCList',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'books.blog.some_permission' => [
                'tab' => 'Blog',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * registerNavigation used by the backend.
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'blog' => [
                'label' => 'Blog',
                'url' => Backend::url('books/blog/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.blog.*'],
                'order' => 500,
            ],
        ];
    }
}
