<?php

namespace Books\Orders;

use Backend;
use Books\Orders\Classes\Contracts\OrderService as OrderServiceContract;
use Books\Orders\Classes\Contracts\OrderReceiptService as OrderReceiptServiceContract;
use Books\Orders\Classes\Services\OrderReceiptService;
use Books\Orders\Classes\Services\OrderService;
use Books\Orders\Components\AuthorSupport;
use Books\Orders\Components\BalanceDeposit;
use Books\Orders\Components\BuyAwards;
use Books\Orders\Components\Order;
use Books\Orders\Console\DiscardOldOrders;
use Books\Orders\Models\BalanceDeposit as DepositModel;
use Books\Orders\Models\OrderProduct;
use Books\Orders\Models\OrderPromocode;
use Config;
use Illuminate\Foundation\AliasLoader;
use System\Classes\PluginBase;
use Books\Orders\Models\Order as OrderModel;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.User', 'Books.Book'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Orders',
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
        parent::register();

        $this->app->bind(OrderServiceContract::class, OrderService::class);
        $this->app->bind(OrderReceiptServiceContract::class, OrderReceiptService::class);

        $this->registerConsoleCommand('book:orders:discard_old_orders', DiscardOldOrders::class);
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        Config::set('orders', Config::get('books.orders::config'));

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
            AuthorSupport::class => 'AuthorSupport',
            BuyAwards::class => 'BuyAwards',
        ];
    }

    /**
     * Registers any backend permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'books.orders.orders' => [
                'tab' => 'Orders',
                'label' => 'Orders permission'
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
        return [
            'orders' => [
                'label' => 'Продажи',
                'icon' => 'icon-leaf',
                'order' => 500,
                'sideMenu' => [
                    'orders' => [
                        'label' => 'Заказы',
                        'icon' => 'icon-leaf',
                        'url' => Backend::url('books/orders/orders'),
                        'permissions' => ['books.orders.*'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $schedule
     *
     * @return void
     */
    public function registerSchedule($schedule): void
    {
        $schedule->command('book:orders:discard_old_orders')->dailyAt('04:10');
    }
}
