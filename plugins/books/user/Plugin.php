<?php namespace Books\User;

use Backend;
use Event;
use RainLab\User\Models\User;
use System\Classes\PluginBase;
use Books\User\Behaviors\BookUser;
use Books\User\Components\BookAccount;
use Books\User\Classes\UserEventHandler;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.User'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'User',
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

        User::extend(function (User $model) {
            $model->implementClassWith(BookUser::class);
            $model->bindEvent('model.afterCreate', function () use ($model) {
                (new UserEventHandler())->afterCreate($model);
            });
        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            BookAccount::class => 'bookAccount'
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
            'books.user.some_permission' => [
                'tab' => 'User',
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
            'user' => [
                'label' => 'User',
                'url' => Backend::url('books/user/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.user.*'],
                'order' => 500,
            ],
        ];
    }
}
