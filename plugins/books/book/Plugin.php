<?php

namespace Books\Book;

use Backend;
use Books\Book\Behaviors\Contentable;
use Books\Book\Behaviors\Prohibitable;
use Books\Book\Behaviors\Trackable;
use Books\Book\Classes\BookService;
use Books\Book\Classes\BookUtilities;
use Books\Book\Classes\ChapterService;
use Books\Book\Classes\Converters\FB2;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Enums\StatsEnum;
use Books\Book\Classes\FB2Manager;
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
use Books\Book\Components\CommercialSales;
use Books\Book\Components\CommercialSalesReports;
use Books\Book\Components\CommercialSalesStatistics;
use Books\Book\Components\CommercialSalesStatisticsDetail;
use Books\Book\Components\DiscountLC;
use Books\Book\Components\EBooker;
use Books\Book\Components\IndexWidgets;
use Books\Book\Components\LCBooker;
use Books\Book\Components\OutOfFree;
use Books\Book\Components\Promocode;
use Books\Book\Components\Reader;
use Books\Book\Components\ReadStatistic;
use Books\Book\Components\Widget;
use Books\Book\Console\CleanHTMLContent;
use Books\Book\FormWidgets\ContentDiff;
use Books\Book\FormWidgets\DeferredComments;
use Books\Book\Jobs\GenreRaterExec;
use Books\Book\Models\Author;
use Books\Book\Models\AwardBook;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Content;
use Books\Book\Models\Content as ContentModel;
use Books\Book\Models\Cycle;
use Books\Book\Models\Discount;
use Books\Book\Models\Edition;
use Books\Book\Models\Pagination;
use Books\Book\Models\Prohibited;
use Books\Book\Models\SystemMessage;
use Books\Book\Models\Tag;
use Books\Book\Models\Tracker;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Books\Collections\Models\Lib;
use Books\Notifications\Console\NotifyUsersAboutTodayDiscounts;
use Books\Profile\Behaviors\Slavable;
use Books\Reposts\behaviors\Shareable;
use Books\User\Classes\CookieEnum;
use Config;
use Event;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Foundation\AliasLoader;
use Mobecan\Favorites\Behaviors\Favorable;
use October\Rain\Database\Models\DeferredBinding;
use RainLab\Location\Behaviors\LocationModel;
use System\Classes\PluginBase;
use System\Models\Revision;
use Tizis\FB2\FB2Controller;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = [
        'RainLab.User',
        'Books.Profile',
        'Books.Breadcrumbs',
    ];

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
        $this->registerConsoleCommand('book:discounts:notify_user_about_today_discounts', NotifyUsersAboutTodayDiscounts::class);
        $this->registerConsoleCommand('book:content:clean_html', CleanHTMLContent::class);
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot(): void
    {
        Config::set('book', Config::get('books.book::config'));

        AliasLoader::getInstance()->alias('FB2', FB2::class);
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
        AliasLoader::getInstance()->alias('CookieEnum', CookieEnum::class);
        AliasLoader::getInstance()->alias('SystemMessage', SystemMessage::class);
        AliasLoader::getInstance()->alias('StatsEnum', StatsEnum::class);
        AliasLoader::getInstance()->alias('Content', ContentModel::class);
        AliasLoader::getInstance()->alias('BUtils', BookUtilities::class);

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
                $model->implementClassWith(Contentable::class);
            });
        }

        foreach ([Edition::class, Chapter::class, Pagination::class] as $class) {
            $class::extend(function ($model) {
                $model->implementClassWith(Trackable::class);
            });
        }

        \Books\Book\Controllers\Book::extendFormFields(function ($form, $model, $context) {
            if (!$model instanceof Book) {
                return;
            }
            $form->addTabFields([
                'stats' => [
                    'type' => 'partial',
                    'path' => '$/books/book/views/stats_relation_form.htm',
                    'tab' => 'Статистика',
                ],
            ]);
        });

        $this->registerBreadcrumbs();
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
            CommercialSales::class => 'CommercialSales',
            CommercialSalesReports::class => 'CommercialSalesReports',
            CommercialSalesStatistics::class => 'CommercialSalesStatistics',
            CommercialSalesStatisticsDetail::class => 'CommercialSalesStatisticsDetail',
            IndexWidgets::class => 'IndexWidgets',
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
                'systemmessage' => [
                    'label' => 'Системные сообщения',
                    'icon' => 'icon-leaf',
                    'url' => Backend::url('books/book/systemmessage'),
                    'permissions' => ['books.book.systemmessage'],
                ],
                'content' => [
                    'label' => 'Отложенное редактирование',
                    'icon' => 'icon-leaf',
                    'url' => Backend::url('books/book/content'),
                    'permissions' => ['books.book.content'],
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

        $times = app()->isProduction() ? 'everyTenMinutes' :'everyMinute';
        $schedule->call(function () {
            GenreRaterExec::dispatch();
        })->{$times}();

        $schedule->call(function () {
            DeferredBinding::cleanUp(1);
        })->dailyAt('03:00');

        $schedule->command('model:prune', [
            '--model' => [Models\Promocode::class, Lib::class],
        ])->dailyAt('03:00');

        $schedule->command('book:discounts:notify_user_about_today_discounts')->dailyAt('03:10');
    }

    public function registerFormWidgets()
    {
        return [
            ContentDiff::class => 'book_content_diff',
        ];
    }

    /**
     * @return void
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('lc-profile', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
        });

        $manager->register('lc-advert', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Реклама');
        });

        $manager->register('lc-awards', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Награды');
        });

        $manager->register('lc-blacklist', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Черный список');
        });

        $manager->register('lc-books', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Книги');
        });

        $manager->register('book-create', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Создание книги');
        });

        $manager->register('lc-read-statistic', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Статистика прочтений');
        });

        $manager->register('lc-comments', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Комментарии');
        });

        $manager->register('lc-discounts', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Скидки');
        });

        $manager->register('lc-notification', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Уведомления');
        });

        $manager->register('lc-privacy', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Приватность');
        });

        $manager->register('lc-blog', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Блог');
        });

        $manager->register('lc-promocodes', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Промокоды');
        });

        $manager->register('lc-referral', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Реферальная программа');
        });

        $manager->register('lc-reposts', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Репосты');
        });

        $manager->register('lc-settings', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Настройки');
        });

        $manager->register('lc-subscribers', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Подписчики');
        });

        $manager->register('lc-subscriptions', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Подписки');
        });
    }
}
