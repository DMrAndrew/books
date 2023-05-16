<?php

namespace Books\Orders;

use Backend;
use Books\Orders\Classes\Contracts\OrderService as OrderServiceContract;
use Books\Orders\Classes\Services\OrderService;
use Books\Orders\Components\BalanceDeposit;
use Books\Orders\Components\Order;
use Books\Orders\Models\BalanceDeposit as DepositModel;
use Books\Orders\Models\OrderProduct;
use Books\Orders\Models\OrderPromocode;
use Illuminate\Foundation\AliasLoader;
use System\Classes\PluginBase;
use Books\Orders\Models\Order as OrderModel;

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
            'name'        => 'Orders',
            'description' => 'No description provided yet...',
            'author'      => 'Books',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(OrderServiceContract::class, OrderService::class);
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        AliasLoader::getInstance()->alias('Order', OrderModel::class);
        AliasLoader::getInstance()->alias('OrderProduct', OrderProduct::class);
        AliasLoader::getInstance()->alias('OrderPromocode', OrderPromocode::class);
        AliasLoader::getInstance()->alias('Deposit', DepositModel::class);
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            Order::class => 'Order',
            BalanceDeposit::class => 'BalanceDeposit',
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
            'books.orders.some_permission' => [
                'tab' => 'Orders',
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
            'orders' => [
                'label'       => 'Orders',
                'url'         => Backend::url('books/orders/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['books.orders.*'],
                'order'       => 500,
            ],
        ];
    }
}
