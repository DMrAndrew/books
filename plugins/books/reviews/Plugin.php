<?php namespace Books\Reviews;

use Config;
use Backend;
use RainLab\User\Models\User;
use System\Classes\PluginBase;
use Books\Reviews\Behaviors\PerformsReviews;

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
            'name' => 'Reviews',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        Config::set('reviews', Config::get('books.reviews::config'));
        User::extend(function (User $model) {
            $model->implementClassWith(PerformsReviews::class);
        });
//        Post::extend(function (RainLab\Blog\Models\Post $model) {
//            $model->implementClassWith(Reviewable::class);
//        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [];
    }

    /**
     * Registers any backend permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'books.reviews.some_permission' => [
                'tab' => 'Reviews',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers backend navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'reviews' => [
                'label' => 'Reviews',
                'url' => Backend::url('books/reviews/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.reviews.*'],
                'order' => 500,
            ],
        ];
    }
}
