<?php
namespace Books\AuthorPrograms;

use Backend;
use Books\AuthorPrograms\behaviors\UserBehavior;
use Books\AuthorPrograms\Components\AuthorProgramMainLC;
use RainLab\User\Models\User;
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
        'Books.Profile'
    ];

    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'AuthorPrograms',
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
        User::extend(fn(User $model) => $model->implementClassWith(UserBehavior::class));
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            AuthorProgramMainLC::class => 'AuthorProgramMainLC',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'books.authorprograms.some_permission' => [
                'tab' => 'AuthorPrograms',
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
            'authorprograms' => [
                'label' => 'AuthorPrograms',
                'url' => Backend::url('books/authorprograms/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.authorprograms.*'],
                'order' => 500,
            ],
        ];
    }
}
