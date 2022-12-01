<?php namespace Books\Book;

use Backend;
use Books\Book\Classes\CoAuthorManager;
use Books\Book\Models\Book;
use Books\Book\Models\Cycle;
use Books\Profile\Behaviors\Profileable;
use October\Rain\Database\Model;
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
    public function pluginDetails()
    {
        return [
            'name' => 'Book',
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
        //TODO extend
//        collect([
//            Book::class,
//            Cycle::class
//        ])->each(fn(Model $model) => $model->implementClassWith(Profileable::class));

//        CoAuthorManager::bindOnInsertCoAuthorEvent();

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Books\Book\Components\MyComponent' => 'myComponent',
        ];
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
            'books.book.some_permission' => [
                'tab' => 'Book',
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
            'book' => [
                'label' => 'Book',
                'url' => Backend::url('books/book/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.book.*'],
                'order' => 500,
            ],
        ];
    }
}
