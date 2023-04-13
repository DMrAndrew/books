<?php

namespace Books\User;

use Backend;
use Books\User\Behaviors\BookUser;
use Books\User\Behaviors\CountryTranslate;
use Books\User\Classes\SearchManager;
use Books\User\Classes\UserEventHandler;
use Books\User\Classes\UserSettingsEnum;
use Books\User\Components\BookAccount;
use Books\User\Components\Searcher;
use Books\User\Components\UserSettingsLC;
use Books\User\Models\Settings;
use Event;
use Illuminate\Foundation\AliasLoader;
use Monarobase\CountryList\CountryList;
use ProtoneMedia\LaravelCrossEloquentSearch\Search;
use RainLab\Location\Behaviors\LocationModel;
use RainLab\Location\Models\Country;
use RainLab\User\Models\User;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = [
        'RainLab.User',
        'RainLab.Notify',
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
        //test
        Event::listen('rainlab.user.register', function (User $model) {
            (new UserEventHandler())->afterCreate($model->fresh());
        });
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot(): void
    {
        AliasLoader::getInstance()->alias('User', User::class);
        AliasLoader::getInstance()->alias('Search', Search::class);
        AliasLoader::getInstance()->alias('SearchManager', SearchManager::class);
        AliasLoader::getInstance()->alias('Country', Country::class);
        AliasLoader::getInstance()->alias('BookSettings', Settings::class);
        AliasLoader::getInstance()->alias('CountryList', CountryList::class);
        AliasLoader::getInstance()->alias('UserSettingsEnum', UserSettingsEnum::class);
        Country::extend(function (Country $country) {
            $country->implementClassWith(CountryTranslate::class);
        });

        User::extend(function (User $model) {
            $model->implementClassWith(BookUser::class);
            $model->implementClassWith(LocationModel::class);
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
