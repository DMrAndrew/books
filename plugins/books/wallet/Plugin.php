<?php

declare(strict_types=1);

namespace Books\Wallet;

use Backend;
use Bavix\Wallet\WalletServiceProvider;
use Event;
use Illuminate\Database\ConnectionResolverInterface;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails(): array
    {
        return [
            'name'        => 'Wallet',
            'description' => 'Easy work with virtual wallet',
            'author'      => 'Books',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register(): void
    {
        // README: this fix error "BindingResolutionException with message 'Target [ConnectionResolverInterface] is not instantiable while building"
        $this->app->alias('db', ConnectionResolverInterface::class);
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->extendOrdersController();
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return [
            'books.wallet.wallet' => [
                'tab' => 'Wallet',
                'label' => 'Wallet permission'
            ],
            'books.wallet.transaction' => [
                'tab' => 'Wallet',
                'label' => 'Transaction permission'
            ],
        ];
    }

    /**
     * @return void
     */
    public function extendOrdersController(): void
    {
        /**
         * Навигация
         */
        Event::listen('backend.menu.extendItems', function ($manager) {
            $manager->addSideMenuItems('Books.Orders', 'orders', [
                'wallets' => [
                    'label' => 'Кошельки',
                    'icon' => 'icon-leaf',
                    'url' => Backend::url('books/wallet/wallet'),
                    'permissions' => ['books.wallet.wallet'],
                ],
                'transactions' => [
                    'label' => 'Транзакции',
                    'icon' => 'icon-leaf',
                    'url' => Backend::url('books/wallet/transaction'),
                    'permissions' => ['books.wallet.transaction'],
                ],
            ]);
        });
    }
}
