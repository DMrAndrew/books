<?php

namespace Books\User;

use Books\Reposts\behaviors\CanShare;
use Books\User\Behaviors\BookUser;
use Books\User\Behaviors\CountryTranslate;
use Books\User\Classes\CookieEnum;
use Books\User\Classes\SearchManager;
use Books\User\Classes\UserSettingsEnum;
use Books\User\Components\BookAccount;
use Books\User\Components\Searcher;
use Books\User\Components\UserSettingsLC;
use Books\User\Models\Settings;
use Books\Wallet\Behaviors\WalletBehavior;
use Books\User\Models\TempAdultPass;
use Event;
use Flash;
use Illuminate\Foundation\AliasLoader;
use Monarobase\CountryList\CountryList;
use ProtoneMedia\LaravelCrossEloquentSearch\Search;
use RainLab\Location\Behaviors\LocationModel;
use RainLab\Location\Models\Country;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = [
        'RainLab.User',
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
     *
     * @return void
     */
    public function register(): void
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot(): void
    {
        Event::listen('rainlab.user.getNotificationVars', function (User $model) {
            return false;
        });


        Event::listen('mobecan.socialconnect.registerUser', function (array $provider_details, array $user_details) {

            if ($user_details['avatar'] ?? false) {
                unset($user_details['avatar']);
            }

            return new User($user_details);

        }, 1);

        Event::listen('mobecan.socialconnect.handleLogin', function ($provider_details, $provider_response, User $user) {
            if (!$user->exists) {
                CookieEnum::guest->setForever($user->toArray());
                Flash::add('post_register_required',1);
                return redirect()->refresh();
            }
        }, 1);

        Event::listen('rainlab.user.beforeRegister', function (&$user) {
            $user['required_post_register'] = 0;
        }, 1);
        AliasLoader::getInstance()->alias('User', User::class);
        AliasLoader::getInstance()->alias('Search', Search::class);
        AliasLoader::getInstance()->alias('SearchManager', SearchManager::class);
        AliasLoader::getInstance()->alias('Country', Country::class);
        AliasLoader::getInstance()->alias('BookSettings', Settings::class);
        AliasLoader::getInstance()->alias('CountryList', CountryList::class);
        AliasLoader::getInstance()->alias('UserSettingsEnum', UserSettingsEnum::class);
        AliasLoader::getInstance()->alias('TempAdultPass', TempAdultPass::class);
        Country::extend(function (Country $country) {
            $country->implementClassWith(CountryTranslate::class);
        });

        User::extend(function (User $model) {
            $model->implementClassWith(BookUser::class);
            $model->implementClassWith(LocationModel::class);
            $model->implementClassWith(CanShare::class);
            $model->implementClassWith(WalletBehavior::class);
        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents(): array
    {
        return [
            BookAccount::class => 'bookAccount',
            Searcher::class => 'searcher',
            UserSettingsLC::class => 'userSettingsLC',
        ];
    }
}
