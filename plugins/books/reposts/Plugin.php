<?php namespace Books\Reposts;

use Backend;
use Books\Profile\Behaviors\Slavable;
use Books\Reposts\Components\Reposter;
use Books\Reposts\Components\RepostsLC;
use Books\Reposts\Models\Repost;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    public $require = [
        'Books.Profile',
    ];

    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Reposts',
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
        Repost::extend(function ($model) {
            $model->implementClassWith(Slavable::class);
        });
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            Reposter::class => 'reposter',
            RepostsLC::class => 'repostsLC',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'books.reposts.some_permission' => [
                'tab' => 'Reposts',
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
            'reposts' => [
                'label' => 'Reposts',
                'url' => Backend::url('books/reposts/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.reposts.*'],
                'order' => 500,
            ],
        ];
    }
}
