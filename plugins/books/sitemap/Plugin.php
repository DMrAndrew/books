<?php namespace Books\Sitemap;

use Backend;
use Books\Sitemap\Console\GenerateSitemap;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    public $require = [
        'Books.Book',
        'Books.Blog',
    ];

    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Sitemap',
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
        if ($this->app->runningInConsole()) {
            $this->registerConsoleCommand('fb:sitemap:generate', GenerateSitemap::class);
        }
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        //
    }

    /**
     * @param string $schedule
     */
    public function registerSchedule($schedule): void
    {
        $schedule->command('sitemap:generate')->daily();
    }
}
