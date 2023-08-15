<?php namespace Books\Blacklists;

use Backend;
use Books\Blacklists\Behaviors\Blacklistable;
use Books\Blacklists\Components\Blacklist;
use Books\Profile\Models\Profile;
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
            'name' => 'Blacklists',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-leaf'
        ];
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        Profile::extend(function (Profile $model) {
            $model->implementClassWith(Blacklistable::class);
        });
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            Blacklist::class => 'Blacklist',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'books.blacklists.some_permission' => [
                'tab' => 'Blacklists',
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
            'blacklists' => [
                'label' => 'Blacklists',
                'url' => Backend::url('books/blacklists/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.blacklists.*'],
                'order' => 500,
            ],
        ];
    }
}
