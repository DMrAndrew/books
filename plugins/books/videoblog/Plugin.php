<?php namespace Books\Videoblog;

use Backend;
use Books\Blog\Classes\Services\PostService;
use Books\Blog\Classes\Services\VideoBlogPostService;
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
        //
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

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
        return []; // Remove this line to activate

        return [
            'videoblog' => [
                'label' => 'Videoblog',
                'url' => Backend::url('books/videoblog/mycontroller'),
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
