<?php

namespace Books\Notifications;

use Backend;
use Books\Notifications\Classes\Behaviors\NotificationsModel;
use Books\Notifications\Classes\Conditions\SettingsIsEnabled;
use Books\Notifications\Classes\Events\TestEvent;
use Books\Profile\Models\Profile;
use RainLab\Notify\Classes\Notifier;
use RainLab\Notify\NotifyRules\SaveDatabaseAction;
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
        'Books.User',
        'Books.Profile',
        'Books.Book',
        'Books.Comments',
        'RainLab.Notify',
    ];

    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails(): array
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
    public function register(): void
    {
        //
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot(): void
    {
        $this->extendModels();

        /*
         * Compatability with RainLab.Notify
         */
        $this->extendSaveDatabaseAction();

        Notifier::bindEvents([
            'test.events' => TestEvent::class,
        ]);
    }

    /**
     * @return array
     */
    public function registerNotificationRules(): array
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
     * registerComponents used by the frontend.
     */
    public function registerComponents(): array
    {
        return [

        ];
    }

    /**
     * @return void
     */
    protected function extendModels(): void
    {
        User::extend(static function (User $model): void {
            $model->implementClassWith(NotificationsModel::class);
        });

        Profile::extend(static function (Profile $model): void {
            $model->implementClassWith(NotificationsModel::class);
        });
    }

    /**
     * @return void
     */
    protected function extendSaveDatabaseAction(): void
    {
        if (!class_exists(SaveDatabaseAction::class)) {
            return;
        }

        SaveDatabaseAction::extend(static function (SaveDatabaseAction $action) {
            $action->addTableDefinition([
                'label' => 'Аккаунт',
                'class' => User::class,
                'relation' => 'notifications',
                'param' => 'user',
            ]);

            $action->addTableDefinition([
                'label' => 'Аккаунт',
                'class' => User::class,
                'relation' => 'notifications',
                'param' => 'user',
            ]);
        });
    }
}
