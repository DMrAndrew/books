<?php namespace Books\Videoblog;

use Backend;
use Books\Profile\Models\Profile;
use Books\Videoblog\Behaviors\HasVideoBlog;
use Books\Videoblog\Classes\Services\VideoBlogPostService;
use Books\Videoblog\Components\VideoBlogLC;
use Books\Videoblog\Components\VideoBlogLCList;
use Books\Videoblog\Components\VideoBlogList;
use Books\Videoblog\Components\VideoBlogPost;
use Books\Videoblog\Components\VideoBlogPostCard;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    public $require = [
        'RainLab.User',
        'Books.User',
        'Books.Blacklists',
        'Books.Breadcrumbs',
        'Books.Profile'
    ];

    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Videoblog',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-play-circle-o'
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
            $model->implementClassWith(HasVideoBlog::class);
        });
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            VideoBlogPost::class => 'VideoBlogPost',
            VideoBlogPostCard::class => 'VideoBlogPostCard',
            VideoBlogList::class => 'VideoBlogList',
            VideoBlogLC::class => 'VideoBlogLC',
            VideoBlogLCList::class => 'VideoBlogLCList',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return [
            'books.videoblog.some_permission' => [
                'tab' => 'Videoblog',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * registerNavigation used by the backend.
     */
    public function registerNavigation()
    {
        return [
            'videoblog' => [
                'label' => 'Videoblog',
                'url' => Backend::url('books/videoblog/videoblog'),
                'icon' => 'icon-play-circle-o',
                'permissions' => ['books.videoblog.*'],
                'order' => 500,
            ],
        ];
    }


    public function registerSchedule($schedule): void
    {
        $schedule->call(function () {
            VideoBlogPostService::delayedPublications();
        })->everyMinute();
    }
}
