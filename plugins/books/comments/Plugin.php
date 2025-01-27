<?php namespace Books\Comments;

use Backend;
use Books\Blog\Models\Post;
use Books\Book\Models\Book;
use Books\Comments\behaviors\Commentable;
use Books\Comments\Components\Comments;
use Books\Comments\Components\CommentsLC;
use Books\Comments\Models\Comment;
use Books\Profile\Models\Profile;
use Books\Videoblog\Models\Videoblog;
use Illuminate\Foundation\AliasLoader;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = [
        'Books.Blacklists',
    ];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Comments',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        foreach ([Profile::class, Book::class, Post::class, Videoblog::class] as $class) {
            $class::extend(function ($model) {
                $model->implementClassWith(Commentable::class);
            });
        }
        AliasLoader::getInstance()->alias('Comment', Comment::class);
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            Comments::class => 'comments',
            CommentsLC::class => 'commentsLC',
        ];
    }

    /**
     * Registers any backend permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'books.comments.some_permission' => [
                'tab' => 'Comments',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers backend navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'comments' => [
                'label' => 'Comments',
                'url' => Backend::url('books/comments/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.comments.*'],
                'order' => 500,
            ],
        ];
    }
}
