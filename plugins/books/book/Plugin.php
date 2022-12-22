<?php namespace Books\Book;

use Backend;
use Books\Book\Classes\BookManager;
use Books\Book\Classes\CoAuthorManager;
use Books\Book\Components\Booker;
use Books\Book\Components\Chapterer;
use Books\Book\Components\EBooker;
use Books\Book\Components\ECommerceBooker;
use Books\Book\Components\LCBooker;
use Books\Book\Models\Book;
use Books\Book\Models\Cycle;
use Books\Profile\Behaviors\Profileable;
use Event;
use October\Rain\Database\Model;
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
        Event::listen('books.book.created', fn($book) => (new BookManager())->countContentLength($book));
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {

        return [
            Booker::class => 'booker',
            LCBooker::class => 'LCBooker',
            EBooker::class => 'Ebooker',
            ECommerceBooker::class => 'ECommerceBooker',
            Chapterer::class => 'Chapterer',
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
