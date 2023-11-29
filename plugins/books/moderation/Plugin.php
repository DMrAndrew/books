<?php namespace Books\Moderation;

use Backend;
use Books\Moderation\Classes\LaravelDrafts;
use Books\Moderation\ServiceProviders\DraftsServiceProvider;
use Config;
use Illuminate\Foundation\AliasLoader;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Moderation',
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
        $alias = AliasLoader::getInstance();
        $alias->alias('LaravelDrafts', LaravelDrafts::class);

        $this->app->register(DraftsServiceProvider::class);
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        Config::set('moderation', Config::get('books.moderation::config'));
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return [];
    }

    /**
     * registerNavigation used by the backend.
     */
    public function registerNavigation()
    {
        return [];
    }
}
