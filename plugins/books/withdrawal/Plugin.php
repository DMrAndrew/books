<?php namespace Books\Withdrawal;

use Backend;
use Books\Withdrawal\Classes\Contracts\AgreementServiceContract;
use Books\Withdrawal\Classes\Services\AgreementService;
use Books\Withdrawal\Components\WithdrawalForm;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.User', 'Books.Profile'];

    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Withdrawal',
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
        $this->app->bind(AgreementServiceContract::class, AgreementService::class);
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        //
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            WithdrawalForm::class => 'WithdrawalForm',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return [
            'books.withdrawal.access' => [
                'tab' => 'withdrawal',
                'label' => 'access'
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
            'withdrawal' => [
                'label' => 'Вывод средств',
                'url' => Backend::url('books/withdrawal/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.withdrawal.*'],
                'order' => 500,
            ],
        ];
    }
}