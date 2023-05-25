<?php

namespace Books\Collections;

use Backend;
use Books\Book\Models\Book;
use Books\Collections\behaviors\HasLibrary;
use Books\Collections\classes\CollectionEnum;
use Books\Collections\Components\Library;
use Books\Collections\Models\Lib;
use Illuminate\Foundation\AliasLoader;
use Mobecan\Favorites\Behaviors\Favorable;
use RainLab\User\Models\User;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.User', 'Books.Book'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Collections',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-leaf',
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
        AliasLoader::getInstance()->alias('Lib', Lib::class);
        AliasLoader::getInstance()->alias('CollectionEnum', CollectionEnum::class);
        User::extend(function (User $user) {
            $user->implementClassWith(HasLibrary::class);
        });
        Book::extend(function (Book $book) {
            $book->implementClassWith(Favorable::class);
        });
        Lib::extend(function (Lib $lib) {
            $lib->implementClassWith(Favorable::class);
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
            Library::class => 'library',
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
            'books.collections.some_permission' => [
                'tab' => 'Collections',
                'label' => 'Some permission',
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
            'collections' => [
                'label' => 'Collections',
                'url' => Backend::url('books/collections/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.collections.*'],
                'order' => 500,
            ],
        ];
    }
}
