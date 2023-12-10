<?php namespace Books\Certificates;

use Backend;
use Books\Certificates\Behaviors\CertificateRelations;
use Books\Certificates\classes\LoginHandler;
use Books\Certificates\Components\CertificateLC;
use Books\Certificates\Components\CertificateModal;
use Books\Profile\Models\Profile;
use Event;
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
        'Books.User',
        'Books.Blacklists',
        'Books.Breadcrumbs',
        'Books.Profile'
    ];

    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Certificates',
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
        //
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        Event::listen('rainlab.user.login', LoginHandler::class);
        Profile::extend(function (Profile $model) {
            $model->implementClassWith(CertificateRelations::class);
        });
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            CertificateLC::class => 'CertificateLC',
            CertificateModal::class => 'CertificateModal',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate
    }

    /**
     * registerNavigation used by the backend.
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate
    }
}
