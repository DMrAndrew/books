<?php

namespace Books\Payment;

use System\Classes\PluginBase;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.User'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Pyament',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-leaf',
        ];
    }
}
