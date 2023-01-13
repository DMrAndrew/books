<?php namespace Books\Book;

use Event;
use Config;
use Backend;
use Books\Book\Models\Book;
use Books\Book\Models\Cycle;
use System\Classes\PluginBase;
use Books\Book\Models\Chapter;
use Books\Book\Components\Booker;
use Books\Book\Components\EBooker;
use Books\Book\Components\LCBooker;
use Books\Book\Components\Chapterer;
use Illuminate\Foundation\AliasLoader;
use Books\Book\Components\ECommerceBooker;

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
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        AliasLoader::getInstance()->alias('Book', Book::class);
        AliasLoader::getInstance()->alias('Chapter', Chapter::class);
        AliasLoader::getInstance()->alias('Cycle', Cycle::class);
        Config::set('book', Config::get('books.book::config'));
        Event::listen('books.book.parsed', fn(Book $book) => $book->recompute());
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
