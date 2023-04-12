<?php

namespace Books\Notifications;

use Backend;
use Books\Notifications\Classes\Conditions\SettingsIsEnabled;
use Books\Notifications\Classes\Events\TestEvent;
use RainLab\Notify\Classes\Notifier;
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
            'name' => 'Notifications',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-leaf',
        ];
    }

    /**
     * register method, called when the plugin is first registered.
     */
    public function register()
    {
        //
    }

    public function registerNotificationRules()
    {
        return [
            'events' => [
                TestEvent::class,
            ],
            'actions' => [
            ],
            'conditions' => [
                SettingsIsEnabled::class,
            ],
            'groups' => [
                'user' => [
                    'label' => 'User',
                    'icon' => 'icon-user',
                ],
            ],
            'presets' => '$/books/notifications/classes/presets/test.yaml',
        ];
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        Notifier::bindEvents([
            'test.events' => TestEvent::class,
        ]);
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Books\Notifications\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'books.notifications.some_permission' => [
                'tab' => 'Notifications',
                'label' => 'Some permission',
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
            'notifications' => [
                'label' => 'Notifications',
                'url' => Backend::url('books/notifications/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.notifications.*'],
                'order' => 500,
            ],
        ];
    }
}
