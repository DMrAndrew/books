<?php

namespace Books\Book;

use Books\Book\Behaviors\Fillable;
use Books\Book\Behaviors\Prohibitable;
use Books\Book\Behaviors\Trackable;
use Books\Book\Classes\BookService;
use Books\Book\Classes\ChapterService;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\FB2Manager;
use Books\Book\Classes\GenreRater;
use Books\Book\Classes\Rater;
use Books\Book\Classes\StatisticService;
use Books\Book\Classes\WidgetService;
use Books\Book\Components\AboutBook;
use Books\Book\Components\AdvertBanner;
use Books\Book\Components\AdvertLC;
use Books\Book\Components\AwardsLC;
use Books\Book\Components\BookAwards;
use Books\Book\Components\BookCard;
use Books\Book\Components\Booker;
use Books\Book\Components\BookPage;
use Books\Book\Components\Chapterer;
use Books\Book\Components\Commercial;
use Books\Book\Components\DiscountLC;
use Books\Book\Components\EBooker;
use Books\Book\Components\LCBooker;
use Books\Book\Components\OutOfFree;
use Books\Book\Components\Promocode;
use Books\Book\Components\Reader;
use Books\Book\Components\ReadStatistic;
use Books\Book\Components\Widget;
use Books\Book\Models\Author;
use Books\Book\Models\AwardBook;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Cycle;
use Books\Book\Models\Discount;
use Books\Book\Models\Edition;
use Books\Book\Models\Pagination;
use Books\Book\Models\Prohibited;
use Books\Book\Models\Tag;
use Books\Book\Models\Tracker;
use Books\Profile\Behaviors\Slavable;
use Books\Reposts\behaviors\Shareable;
use Config;
use Event;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Foundation\AliasLoader;
use Mobecan\Favorites\Behaviors\Favorable;
use October\Rain\Database\Models\DeferredBinding;
use RainLab\Location\Behaviors\LocationModel;
use System\Classes\PluginBase;
use Tizis\FB2\FB2Controller;
use Backend;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.User', 'Books.Profile'];

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
            'icon' => 'icon-leaf',
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->registerConsoleCommand('model:prune', PruneCommand::class);
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot(): void
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
        AliasLoader::getInstance()->alias('Tracker', Tracker::class);
        AliasLoader::getInstance()->alias('Pagination', Pagination::class);
        AliasLoader::getInstance()->alias('EditionsEnums', EditionsEnums::class);
        AliasLoader::getInstance()->alias('Rater', Rater::class);
        AliasLoader::getInstance()->alias('StatisticService', StatisticService::class);
        AliasLoader::getInstance()->alias('WidgetService', WidgetService::class);
        AliasLoader::getInstance()->alias('Promocode', Models\Promocode::class);
        AliasLoader::getInstance()->alias('Discount', Discount::class);
        AliasLoader::getInstance()->alias('Prohibited', Prohibited::class);
        AliasLoader::getInstance()->alias('FB2Controller', FB2Controller::class);

        $this->extendBooksController();

        Event::listen('books.book.created', fn(Book $book) => $book->createEventHandler());


        Book::extend(function (Book $book) {
            $book->implementClassWith(Favorable::class);
            $book->implementClassWith(Shareable::class);
        });

        Prohibited::extend(function (Prohibited $prohibited) {
            $prohibited->implementClassWith(LocationModel::class);
        });
        foreach (config('book.prohibited') as $class) {
            $class::extend(function ($model) {
                $model->implementClassWith(Prohibitable::class);
            });
        }

        AwardBook::extend(function (AwardBook $award) {
            $award->implementClassWith(Slavable::class);
        });

        foreach ([Chapter::class, Pagination::class] as $class) {
            $class::extend(function ($model) {
                $model->implementClassWith(Fillable::class);
            });
        }

        foreach ([Edition::class, Chapter::class, Pagination::class] as $class) {
            $class::extend(function ($model) {
                $model->implementClassWith(Trackable::class);
            });
        }
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents(): array
    {
        return [
            AboutBook::class => 'AboutBook',
            Booker::class => 'booker',
            EBooker::class => 'ebooker',
            LCBooker::class => 'LCBooker',
            Chapterer::class => 'Chapterer',
            BookPage::class => 'BookPage',
            Reader::class => 'reader',
            BookCard::class => 'bookCard',
            ReadStatistic::class => 'readStatistic',
            Widget::class => 'widget',
            OutOfFree::class => 'OutOfFree',
            Components\Cycle::class => 'cycle',
            Promocode::class => 'promocode',
            BookAwards::class => 'bookAwards',
            AwardsLC::class => 'awardsLC',
            AdvertLC::class => 'advertLC',
            AdvertBanner::class => 'advertBanner',
            DiscountLC::class => 'discountLC',
            Commercial::class => 'commercial',
        ];
    }

    public function extendBooksController(): void
    {
        /**
         * Навигация
         */
        Event::listen('backend.menu.extendItems', function ($manager) {
            $manager->addSideMenuItems('Books.Catalog', 'catalog', [
                'books' => [
                    'label' => 'Книги',
                    'icon' => 'icon-leaf',
                    'url' => Backend::url('books/book/book'),
                    'permissions' => ['books.book.books'],
                ],
                'prohibited' => [
                    'label' => 'Запрещённый контент',
                    'icon' => 'icon-leaf',
                    'url' => Backend::url('books/book/prohibited'),
                    'permissions' => ['books.book.prohibited'],
                ],
                'awards' => [
                    'label' => 'Награды',
                    'icon' => 'icon-leaf',
                    'url' => Backend::url('books/book/awards'),
                    'permissions' => ['books.book.awards'],
                ],
                'tags' => [
                    'label' => 'Теги',
                    'icon' => 'icon-leaf',
                    'url' => Backend::url('books/book/tags'),
                    'permissions' => ['books.book.tags'],
                ],
            ]);
        });
    }

    /**
     * Registers any backend permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'books.book.books' => [
                'tab' => 'Book',
                'label' => 'Books permission',
            ],
            'books.book.prohibited' => [
                'tab' => 'Book',
                'label' => 'Prohibited permission',
            ],
            'books.book.awards' => [
                'tab' => 'Book',
                'label' => 'Awards permission',
            ],
            'books.book.tags' => [
                'tab' => 'Book',
                'label' => 'Tags permission',
            ],
        ];
    }

    public function registerSchedule($schedule): void
    {
        $schedule->call(function () {
            ChapterService::audit();
        })->everyMinute();

        $schedule->call(function () {
            GenreRater::queue();
        })->everyTenMinutes();

        $schedule->call(function () {
            DeferredBinding::cleanUp(1);
        })->dailyAt('03:00');


        $schedule->command('model:prune', [
            '--model' => [Models\Promocode::class],
        ])->dailyAt('03:00');
    }
}
