<?php namespace Books\Catalog;

use Backend;
use Books\Book\Models\Book;
use System\Classes\PluginBase;
use Books\Catalog\Models\Genre;
use Books\Catalog\Components\Genres;
use Illuminate\Foundation\AliasLoader;
use Books\Catalog\Components\FavoriteGenres;
use Mobecan\Favorites\Behaviors\Favoriteable;

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
            'name' => 'Catalog',
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
        AliasLoader::getInstance()->alias('Genre', Genre::class);
        Book::extend(function (Book $book) {
            $book->implementClassWith(Favoriteable::class);
        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {

        return [
            Genres::class => 'genres',
            FavoriteGenres::class => 'favorite_genres',
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
            'books.catalog.some_permission' => [
                'tab' => 'Catalog',
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


        return [
            'catalog' => [
                'label' => 'Каталог',
                'url' => Backend::url('books/catalog/catalog'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.catalog.*'],
                'order' => 500,

                'sideMenu' => [
                    'types' => [
                        'label' => 'Типы книг',
                        'icon' => 'icon-leaf',
                        'url' => Backend::url('books/catalog/type'),
                        'permissions' => ['books.catalog.*']
                    ],
                    'genres' => [
                        'label' => 'Жанры',
                        'icon' => 'icon-leaf',
                        'url' => Backend::url('books/catalog/genre'),
                        'permissions' => ['books.catalog.*']
                    ]
                ]
            ],
        ];
    }
}
