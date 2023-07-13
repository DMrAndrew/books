<?php

declare(strict_types=1);

namespace Books\Wallet;

use Backend;
use Bavix\Wallet\WalletServiceProvider;
use Event;
use Illuminate\Database\ConnectionResolverInterface;
use RainLab\User\Controllers\Users as UsersController;
use RainLab\User\Models\User;
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
        $this->extendUserPluginBackendForms();
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

    private function extendUserPluginBackendForms()
    {
        UsersController::extendFormFields(function ($form, $model, $context) {
            if (!$model instanceof User) {
                return;
            }

            /** Кошелек */
            $form->addTabFields([
                'wallet' => [
                    'type'   => 'partial',
                    'label'   => 'Баланс',
                    'path' => '$/books/wallet/controllers/wallet/_balance.php',
                    'tab' => 'Кошелек',
                    'order' => 1100,
                ],
//                'createBalanceCorrection' => [
//                    'type'   => 'partial',
//                    'label'   => '', //кнопка Корректировка баланса
//                    'path' => '$/books/withdrawal/views/_add_withdraw_button.htm',
//                    'tab' => 'Кошелек',
//                    'order' => 1200,
//                ],
                'transactions' => [
                    'type'   => 'partial',
                    'label'   => 'Список транзакций',
                    'path' => '$/books/wallet/views/_transactions_list.htm',
                    'tab' => 'Кошелек',
                    'order' => 1300,
                ],
            ]);
        });
        UsersController::extend(function (UsersController $controller) {
            $controller->relationConfig = '$/books/wallet/config/config_relation.yaml';
            $controller->implementClassWith(Backend\Behaviors\RelationController::class);
        });
    }
}
