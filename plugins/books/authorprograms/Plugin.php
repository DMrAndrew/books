<?php
namespace Books\AuthorPrograms;

use Backend;
use Books\AuthorPrograms\behaviors\UserBehavior;
use Books\AuthorPrograms\Components\AuthorProgramMainLC;
use Books\AuthorPrograms\Console\AuthorBeforeBirthdayNotificationCommand;
use Books\AuthorPrograms\Console\AuthorBirthdayNotificationCommand;
use Illuminate\Database\Console\PruneCommand;
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
        $this->registerConsoleCommand('authorprograms:before_birthday_notification', AuthorBeforeBirthdayNotificationCommand::class);
        $this->registerConsoleCommand('authorprograms:birthday_notification', AuthorBirthdayNotificationCommand::class);
    }

    public function registerSchedule($schedule)
    {
        $schedule->command('authorprograms:before_birthday_notification')->dailyAt('06:00');
        $schedule->command('authorprograms:birthday_notification')->dailyAt('06:00');
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
    }

    /**
     * registerNavigation used by the backend.
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate
    }
}
