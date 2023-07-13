<?php namespace Books\Withdrawal;

use Backend;
use Books\Withdrawal\Classes\Contracts\AgreementServiceContract;
use Books\Withdrawal\Classes\Services\AgreementService;
use Books\Withdrawal\Components\WithdrawalForm;
use Books\Withdrawal\Components\WithdrawalList;
use Books\Withdrawal\Models\Withdrawal as WithdrawalModel;
use Event;
use Exception;
use Flash;
use Input;
use RainLab\User\Controllers\Users;
use RainLab\User\Controllers\Users as UsersController;
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
        'Books.Profile',
        'Books.Orders'
    ];

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
        $this->extendOrdersController();
        $this->extendUserPluginBackendForms();
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            WithdrawalForm::class => 'WithdrawalForm',
            WithdrawalList::class => 'WithdrawalList',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return [
            'books.withdrawal.withdrawal' => [
                'tab' => 'withdrawal',
                'label' => 'Withdrawal permission'
            ],
        ];
    }

    /**
     * registerNavigation used by the backend.
     */
    public function registerNavigation(): array
    {
        return [];

        /** @deprecated текущий вариант через расширение Orders - не утвержденный */
        return [
            'withdrawal' => [
                'label' => 'Вывод средств',
                'url' => Backend::url('books/withdrawal/withdrawaldata'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.withdrawal.*'],
                'order' => 500,
                'sideMenu' => [
                    'types' => [
                        'label' => 'Договора',
                        'icon' => 'icon-leaf',
                        'url' => Backend::url('books/withdrawal/withdrawaldata'),
                        'permissions' => ['books.catalog.*'],
                    ],
                    'genres' => [
                        'label' => 'Вывод средств',
                        'url' => Backend::url('books/withdrawal/withdrawal'),
                        'icon' => 'icon-leaf',
                        'permissions' => ['books.withdrawal.*'],
                    ],
                ],
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
                'types' => [
                    'label' => 'Договора',
                    'icon' => 'icon-leaf',
                    'url' => Backend::url('books/withdrawal/withdrawaldata'),
                    'permissions' => ['books.catalog.*'],
                ],
                'genres' => [
                    'label' => 'Вывод средств',
                    'url' => Backend::url('books/withdrawal/withdrawal'),
                    'icon' => 'icon-leaf',
                    'permissions' => ['books.withdrawal.*'],
                ],
            ]);
        });
    }

    /**
     * @return string[]
     */
    public function registerMailTemplates(): array
    {
        return [
            'books.withdrawal::mail.agreement_verify',
            'books.withdrawal::mail.admin_agreement_verified',
        ];
    }

    /**
     * @return string[][]
     */
    public function registerMarkupTags(): array
    {
        return [
            'functions' => [
                'formatMoneyAmount' => 'formatMoneyAmount'
            ]
        ];
    }

    private function extendUserPluginBackendForms()
    {
        UsersController::extendFormFields(function ($form, $model, $context) {
            if (!$model instanceof User) {
                return;
            }
            /** Вывод средств */
            $form->addTabFields([
                'balance' => [
                    'type'   => 'partial',
                    'label'   => 'Баланс',
                    'path' => '$/books/withdrawal/controllers/withdrawal/_balance.php',
                    'tab' => 'Вывод средств',
                ],
                'createWithdraw' => [
                    'type'   => 'partial',
                    'label'   => '', //кнопка Вывести средства
                    'path' => '$/books/withdrawal/views/_add_withdraw_button.htm',
                    'tab' => 'Вывод средств'
                ],
                'withdrawals' => [
                    'type'   => 'partial',
                    'label'   => 'История выводов средств',
                    'path' => '$/books/withdrawal/views/_withdrawals_list.htm',
                    'tab' => 'Вывод средств'
                ],
            ]);
        });
        UsersController::extend(function (UsersController $controller) {
            $controller->relationConfig = '$/books/withdrawal/config/config_relation.yaml';
            $controller->implementClassWith(Backend\Behaviors\RelationController::class);

            /** отобразить форму вывода средств */
            $controller->addDynamicMethod('onDisplayWithdrawForm', function () use ($controller) {

                $input = Input::all();
                $userId = (int) $input['userId'];

                $user = User::findOrFail($userId);
                $balanceAmount = $user->proxyWallet()->balance;

                return $controller->makePartial('$/books/withdrawal/views/_add_withdraw_form.php', [
                    'userId'    => $userId,
                    'balance'   => $user->proxyWallet()->balance,
                    'canWithdraw'   => $balanceAmount > 0,
                ]);
            });

            /** вывести/списать средства */
            $controller->addDynamicMethod('onWithdrawUserBalance', function () use ($controller) {

                $input = Input::all();
                $userId = (int) $input['userId'];

                $user = User::findOrFail($userId);
                $balanceAmount = $user->proxyWallet()->balance;

                if ($balanceAmount > 0) {
                    try {
                        $user->withdrawals()->create([
                            'amount' => $balanceAmount,
                            'date' => now(),
                        ]);

                        $user->proxyWallet()->withdraw($balanceAmount);

                    } catch (Exception $ex) {
                        Flash::error($ex->getMessage());
                        return [];
                    }
                }

                return $controller->listRefresh();
            });
        });
    }
}
