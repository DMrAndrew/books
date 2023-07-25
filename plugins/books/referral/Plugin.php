<?php namespace Books\Referral;

use Backend;
use Books\Referral\Behaviours\ReferralProgram;
use Books\Referral\Components\LCReferralStatistics;
use Books\Referral\Components\LCReferrer;
use Books\Referral\Components\ReferralLink;
use Books\Referral\Contracts\ReferralServiceContract;
use Books\Referral\Services\ReferralService;
use Event;
use Exception;
use Log;
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
        'RainLab.Orders',
        'RainLab.Wallet',
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
        $this->app->bind(ReferralServiceContract::class, ReferralService::class);
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        User::extend(function (User $model) {
            $model->implementClassWith(ReferralProgram::class);
        });

        Event::listen('rainlab.user.login', function($user) {
            try {
                $referralService = app(ReferralServiceContract::class);
                $referralService->processReferralCookie();
                $referralService->forgetReferralCookie();
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        });
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            LCReferrer::class => 'LCReferrer',
            ReferralLink::class => 'ReferralLink',
            LCReferralStatistics::class => 'LCReferralStatistics',
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
        return [];

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
