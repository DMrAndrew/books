<?php namespace Books\Referral;

use Backend;
use Books\Referral\Behaviours\ReferralProgram;
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
    ];

    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Referral',
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
        User::extend(function (User $model) {
            $model->implementClassWith(ReferralProgram::class);
        });
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Books\Referral\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return [
            'books.referral.*' => [
                'tab' => 'Referral',
                'label' => 'Реферальная программа'
            ],
        ];
    }

    /**
     * registerNavigation used by the backend.
     */
    public function registerNavigation()
    {
        return [
            'referral' => [
                'label' => 'Партнеры реферальной программы',
                'url' => Backend::url('books/referral/partners'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.referral.*'],
                'order' => 500,
            ],
        ];
    }
}
