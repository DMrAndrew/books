<?php

declare(strict_types=1);

namespace Books\Wallet;

use Backend;
use Books\Profile\Contracts\OperationHistoryService as OperationHistoryServiceContract;
use Event;
use Exception;
use Flash;
use Illuminate\Database\ConnectionResolverInterface;
use Input;
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
                'tab' => 'Кошелек',
                'label' => 'Управление кошельками'
            ],
            'books.wallet.transaction' => [
                'tab' => 'Кошелек',
                'label' => 'Управление транзакциями'
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
        /**
         * Колонка `Баланс кошелька` в списке пользователей
         */
        UsersController::extendListColumns(function ($widget, $model) {
            if (!$model instanceof User)
                return;

            $widget->addColumns([
                'balance_amount' => [
                    'label' => 'Баланс',
                    'relation' => 'wallet',
                    'select' => 'balance',
                    'searchable' => false,
                    'sortable' => true,
                ]
            ]);
        });

        /**
         * Фильтры по Балансу в списке пользователей
         */
        UsersController::extendListFilterScopes(function ($filter) {
            if (!$filter->model instanceof User) {
                return;
            }

            $filter->addScopes(
                [
                    'wallet' => [
                        'label' => 'Баланс',
                        'type' => 'numberrange',
                        'scope' => 'balanceAmountInRange',
                    ]
                ]
            );
        });

        /**
         * Поля в форме пользователя
         */
        UsersController::extendFormFields(function ($form, $model, $context) {
            if (!$model instanceof User) {
                return;
            }

            /** Кошелек */
            $form->addTabFields([
                'wallet' => [
                    'type'   => 'partial',
                    'label'   => 'Баланс',
                    'path' => '$/books/wallet/controllers/wallet/_balance_field.php',
                    'tab' => 'Кошелек',
                    'order' => 1400,
                ],
                'createBalanceCorrection' => [
                    'type'   => 'partial',
                    'label'   => '', //кнопка Корректировка баланса
                    'path' => '$/books/wallet/views/_add_balance_correction_button.htm',
                    'tab' => 'Кошелек',
                    'order' => 1500,
                ],
                'transactions' => [
                    'type'   => 'partial',
                    'label'   => 'Список транзакций',
                    'path' => '$/books/wallet/views/_transactions_list.htm',
                    'tab' => 'Кошелек',
                    'order' => 1600,
                ],
            ]);
        });

        UsersController::extend(function (UsersController $controller) {
            $controller->relationConfig = '$/books/wallet/config/config_relation.yaml';
            $controller->implementClassWith(Backend\Behaviors\RelationController::class);
            $controller->implementClassWith(Backend\Behaviors\ListController::class);

            /** Отобразить форму корректировки баланса */
            $controller->addDynamicMethod('onDisplayBalanceCorrectionForm', function () use ($controller) {

                $input = Input::all();
                $userId = (int) $input['userId'];

                $user = User::findOrFail($userId);
                $currentBalanceAmount = $user->proxyWallet()->balance;

                return $controller->makePartial('$/books/wallet/views/_add_balance_correction_form.php', [
                    'userId'    => $userId,
                    'currentBalanceAmount'   => $currentBalanceAmount,
                ]);
            });

            /** Откорректировать баланс */
            $controller->addDynamicMethod('onCorrectionBalance', function () use ($controller) {
                try {
                    $input = Input::all();
                    $userId = (int) $input['userId'];

                    if (!is_numeric($input['targetBalance'])) {
                        throw new Exception('Баланс должен быть числом');
                    }

                    $targetBalance = (int) $input['targetBalance'];
                    $description = substr(trim((string)$input['balance_correction_description']), 0, 1000);

                    $user = User::findOrFail($userId);
                    $currentBalanceAmount = $user->proxyWallet()->balance;

                    if ($targetBalance < 0) {
                        throw new Exception('Баланс не может быть отрицательным');
                    }

                    // calculate diff
                    $diffAmount = $targetBalance - $currentBalanceAmount;
                    $transactionMetaData = mb_strlen($description) > 0
                        ? ['Корректировка баланса' => $description]
                        : [];

                    if ($diffAmount > 0) {
                        $user->proxyWallet()->deposit(abs($diffAmount), $transactionMetaData);
                    } elseif ($diffAmount < 0) {
                        $user->proxyWallet()->withdraw(abs($diffAmount), $transactionMetaData);
                    }

                    $operationHistoryService = app(OperationHistoryServiceContract::class);
                    $operationHistoryService->addBalanceCorrection($user, $diffAmount);

                    Flash::success("Выполнена корректировка на сумму {$diffAmount} рублей");

                } catch (Exception $ex) {
                    Flash::error($ex->getMessage());
                    return [];
                }

                return $controller->listRefresh();
            });
        });
    }
}
