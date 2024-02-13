<?php

namespace Books\Chat;

use Backend;
use Books\Chat\Components\Messenger;
use Broadcast;
use Musonza\Chat\ChatServiceProvider;
use RainLab\User\Classes\AuthMiddleware;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = ['Books.User'];
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Chat',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-leaf',
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
       Broadcast::routes(['middleware' => ['web']]);
        require_once __DIR__.'/channels.php';
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            Messenger::class => 'Messenger',
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
            'books.chat.some_permission' => [
                'tab' => 'Chat',
                'label' => 'Some permission',
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
            'chat' => [
                'label' => 'Chat',
                'url' => Backend::url('books/chat/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.chat.*'],
                'order' => 500,
            ],
        ];
    }
}
