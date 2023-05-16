<?php

declare(strict_types=1);

namespace Books\Wallet;

use Backend;
use Bavix\Wallet\WalletServiceProvider;
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

    }
}
