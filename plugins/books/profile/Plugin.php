<?php namespace Books\Profile;

use Config;
use Backend;
use RainLab\User\Models\User;
use System\Classes\PluginBase;
use Books\Profile\Behaviors\HasProfile;
use Books\Profile\Behaviors\Profileable;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{


    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Profile',
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

        Config::set('profile', Config::get('books.profile::config'));
        User::extend(function (User $model) {
            $model->implementClassWith(HasProfile::class);
        });
        foreach (config('profile.profileable') ?? [] as $class) {
            $class::extend(function ($model) {
                $model->implementClassWith(Profileable::class);
            });
        }

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Books\Profile\Components\MyComponent' => 'myComponent',
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
            'books.profile.some_permission' => [
                'tab' => 'Profile',
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
            'profile' => [
                'label' => 'Profile',
                'url' => Backend::url('books/profile/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.profile.*'],
                'order' => 500,
            ],
        ];
    }
}
