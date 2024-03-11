<?php

namespace Books\User;

use Books\Reposts\behaviors\CanShare;
use Books\User\Behaviors\BookUser as BookUserBehavior;
use Books\User\Behaviors\CanWithdraw;
use Books\User\Behaviors\CountryTranslate;
use Books\User\Classes\CookieEnum;
use Books\User\Classes\SearchManager;
use Books\User\Classes\UserSettingsEnum;
use Books\User\Components\BookAccount;
use Books\User\Components\Searcher;
use Books\User\Components\UserSettingsLC;
use Books\User\Models\Settings;
use Books\User\Models\TempAdultPass;
use Books\User\Models\User as BookUser;
use Books\Wallet\Behaviors\WalletBehavior;
use Event;
use Flash;
use ProtoneMedia\LaravelCrossEloquentSearch\Search;
use RainLab\Location\Behaviors\LocationModel;
use RainLab\Location\Models\Country;
use RainLab\User\Models\User;
use System\Classes\PluginBase;
use ValidationException;
use Validator;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = [
        'RainLab.User',
    ];

    protected array $implements = [
        User::class => [
            BookUserBehavior::class,
            LocationModel::class,
            CanShare::class,
            WalletBehavior::class,
            CanWithdraw::class
        ]
    ];

    protected array $aliases = [
        'User' => User::class,
        'BookUser' => BookUser::class,
        'Search' => Search::class,
        'SearchManager' => SearchManager::class,
        'Country' => Country::class,
        'BookSettings' => Settings::class,
        'UserSettingsEnum' => UserSettingsEnum::class,
        'TempAdultPass' => TempAdultPass::class
    ];


    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'User',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-leaf',
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     */
    public function register(): void
    {
    }

    /**
     * Boot method, called right before the request route.
     */
    public function boot(): void
    {
        Event::listen('rainlab.user.getNotificationVars', fn() => false);

        Event::listen('mobecan.socialconnect.registerUser', function (array $provider_details, array $user_details) {
            try {
                if ($user_details['avatar'] ?? false) {
                    unset($user_details['avatar']);
                }

                $user = new User();

                $v = Validator::make($user_details, [
                    'email' => $user->rules['email'],
                ], $user->customMessages);

                if ($v->fails()) {
                    throw new ValidationException($v);
                }

                $user->fill($user_details);

                return $user;
            } catch (\Exception $exception) {
                $msg = $exception instanceof ValidationException ?
                    $exception->getMessage() :
                    'Не удалось выполнить вход. Обратитесь к администратору.';

                Flash::error($msg);
                abort(redirect('/'));
            }
        });

        Event::listen('mobecan.socialconnect.handleLogin',
            function ($provider_details, $provider_response, User $user) {
                if (!$user->exists) {
                    CookieEnum::guest->set($user->toArray());
                    Flash::add('post_register_required', 1);

                    return redirect()->refresh();
                }
                return;
            });


        Event::listen('rainlab.user.beforeRegister',
            fn(&$user) => array_set($user, 'required_post_register', 0));
        Event::listen('rainlab.user.register',
            fn($user, $data) => CookieEnum::guest->forget());

        loadAlias($this->aliases);
        loadImplements($this->implements);
    }

    /**
     * Registers any front-end components implemented in this plugin.
     */
    public function registerComponents(): array
    {
        return [
            BookAccount::class => 'bookAccount',
            Searcher::class => 'searcher',
            UserSettingsLC::class => 'userSettingsLC',
        ];
    }

    public function registerSchedule($schedule): void
    {
        $schedule->command('model:prune', [
            '--model' => [TempAdultPass::class],
        ])->dailyAt('03:10');
    }
}
