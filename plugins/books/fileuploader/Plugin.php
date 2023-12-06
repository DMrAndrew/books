<?php namespace Books\FileUploader;

use Books\FileUploader\Components\AudioUploader;
use Books\FileUploader\Components\FileUploader;
use Books\FileUploader\Components\ImageUploader;
use System\Classes\PluginBase;

/**
 * Uploader Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Uploader fork',
            'description' => '',
        ];
    }

    public function registerComponents()
    {
        return [
           FileUploader::class  => 'fileUploader',
           ImageUploader::class => 'imageUploader',
           AudioUploader::class => 'imageUploader',
        ];
    }
}
