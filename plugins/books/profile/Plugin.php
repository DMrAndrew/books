<?php namespace Books\Profile;

use Event;
use Config;
use Backend;
use RainLab\User\Models\User;
use System\Classes\PluginBase;
use Books\Profile\Components\Profile;
use Books\Profile\Behaviors\HasProfile;
use Books\Profile\Behaviors\Profileable;
use RainLab\User\Controllers\Users as UsersController;
use ValidationException;

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

        Event::listen('backend.form.extendFields', function ($widget) {
            if (!$widget->getController() instanceof UsersController)
                return;
            if (!$widget->model instanceof User)
                return;
            if (!in_array($widget->getContext(), ['update', 'preview']))
                return;

            $widget->addFields([
                'birthday' => [
                    'label' => 'Дата рождения',
                    'type' => 'datepicker',
                    'mode' => 'date',
                    'span' => 'auto',
                    'tab' =>  'Профиль'
                ],
            ]);
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
            Profile::class => 'profile',
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
