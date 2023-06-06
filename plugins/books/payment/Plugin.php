<?php
declare(strict_types=1);

namespace Books\Payment;

use Albakov\LaravelCloudPayments\Facade as CloudPaymentsFacade;
use Albakov\LaravelCloudPayments\ServiceProvider as CloudPaymentsProvider;
use Illuminate\Foundation\AliasLoader;
use App;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = [
        'RainLab.User',
        'Books.Orders',
    ];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Payment',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-leaf',
        ];
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [];
    }

    public function boot() {
        App::register(CloudPaymentsProvider::class);

        AliasLoader::getInstance()->alias('CloudPayments', CloudPaymentsFacade::class);
    }
}
