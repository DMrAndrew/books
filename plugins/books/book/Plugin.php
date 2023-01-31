<?php namespace Books\Book;

use Backend;
use Books\Book\Classes\BookService;
use Books\Book\Classes\FB2Manager;
use Books\Book\Components\AboutBook;
use Books\Book\Components\Booker;
use Books\Book\Components\BookPage;
use Books\Book\Components\Chapterer;
use Books\Book\Components\EBooker;
use Books\Book\Components\LCBooker;
use Books\Book\Components\Reader;
use Books\Book\Models\Author;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Cycle;
use Books\Book\Models\Edition;
use Books\Book\Models\Tag;
use Config;
use Event;
use Illuminate\Foundation\AliasLoader;
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
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        Config::set('book', Config::get('books.book::config'));

        AliasLoader::getInstance()->alias('Book', Book::class);
        AliasLoader::getInstance()->alias('Chapter', Chapter::class);
        AliasLoader::getInstance()->alias('Cycle', Cycle::class);
        AliasLoader::getInstance()->alias('Tag', Tag::class);
        AliasLoader::getInstance()->alias('Edition', Edition::class);
        AliasLoader::getInstance()->alias('Author', Author::class);
        AliasLoader::getInstance()->alias('FB2Manager', FB2Manager::class);
        AliasLoader::getInstance()->alias('BookService', BookService::class);

        Event::listen('books.book.created', fn(Book $book) => $book->setSortOrder());
        Event::listen('books.book.parsed', fn(Book $book) => $book->ebook?->recompute());
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {

        return [
            AboutBook::class => 'AboutBook',
            Booker::class => 'booker',
            EBooker::class => 'ebooker',
            LCBooker::class => 'LCBooker',
            Chapterer::class => 'Chapterer',
            BookPage::class => 'BookPage',
            Reader::class => 'reader',
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
