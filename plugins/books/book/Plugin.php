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
use Books\Book\Components\AudioBooker;
use Books\Book\Components\AudioChapterer;
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
use Books\Book\Components\ReaderAudio;
use Books\Book\Components\ReadStatistic;
use Books\Book\Components\SaleTagBlock;
use Books\Book\Components\Widget;
use Books\Book\Console\CleanHTMLContent;
use Books\Book\FormWidgets\ContentDiff;
use Books\Book\FormWidgets\DeferredComments;
use Books\Book\Jobs\GenreRaterExec;
use Books\Book\Jobs\Paginate;
use Books\Book\Jobs\Repaginate;
use Books\Book\Models\AudioReadProgress;
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
use Illuminate\Support\Facades\Log;
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
        'Books.AuthorPrograms',
        'Books.Moderation',
    ];

    protected array $implements = [
        Book::class => [
            Favorable::class,
            Shareable::class
        ],
        Prohibited::class => LocationModel::class
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


        loadAlias(config('book.aliases', []));
        loadImplements($this->implements);

        $this->extendBooksController();

        Event::listen('books.book.created', fn(Book $book) => $book->createEventHandler());

        foreach (config('book.prohibited') as $class) {
            $class::extend(fn($model) => $model->implementClassWith(Prohibitable::class));
        }


        foreach ([Chapter::class, Pagination::class] as $class) {
            $class::extend(fn($model) => $model->implementClassWith(Contentable::class));
        }

        foreach ([Edition::class, Chapter::class, Pagination::class] as $class) {
            $class::extend(fn($model) => $model->implementClassWith(Trackable::class));
        }

        Controllers\Book::extendFormFields(function ($form, $model) {
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
            AudioBooker::class => 'audiobooker',
            LCBooker::class => 'LCBooker',
            Chapterer::class => 'Chapterer',
            AudioChapterer::class => 'AudioChapterer',
            BookPage::class => 'BookPage',
            Reader::class => 'reader',
            ReaderAudio::class => 'readeraudio',
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
            SaleTagBlock::class => 'SaleTagBlock',
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

        $schedule->call(function () {
            Repaginate::dispatch();
        })->dailyAt('05:00');

        $schedule->call(fn() => GenreRaterExec::dispatch())->everyTenMinutes();

        $schedule->call(fn() => DeferredBinding::cleanUp(1))->dailyAt('03:00');


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
        $breadcrumbs = [
            'lc-profile' => 'Профиль',
            'lc-advert' => 'Реклама',
            'lc-awards' => 'Награды',
            'lc-blacklist' => 'Черный список',
            'lc-books' => 'Книги',
            'lc-create' => 'Создание книги',
            'lc-read-statistic' => 'Статистика прочтений',
            'lc-comments' => 'Комментарии',
            'lc-discounts' => 'Скидки',
            'lc-notification' => 'Уведомления',
            'lc-privacy' => 'Приватность',
            'lc-blog' => 'Блог',
            'lc-videoblog' => 'Видеоблог',
            'lc-promocodes' => 'Промокоды',
            'lc-referral' => 'Реферальная программа',
            'lc-reposts' => 'Репосты',
            'lc-settings' => 'Настройки',
            'lc-subscribers' => 'Подписчики',
            'lc-subscriptions' => 'Подписки',
        ];

        $manager = app(BreadcrumbsManager::class);

        foreach ($breadcrumbs as $url => $title) {
            $manager->register($url, function (BreadcrumbsGenerator $trail) use ($title) {
                $trail->parent('lc') xor ($title and $trail->push($title));
            });
        }
    }

    /**
     * @return array []
     */
    public function registerMarkupTags(): array
    {
        return [
            'functions' => [
                'humanFileSize' => function (mixed $kilobytes) {
                    return humanFileSize($kilobytes);
                },
                'humanTime' => function (mixed $seconds) {
                    return humanTime($seconds);
                },
                'humanTimeShort' => function (mixed $seconds) {
                    return humanTimeShort($seconds);
                },
                'save_user_audio_read_pregress_delay_in_seconds' => function () {
                    return AudioReadProgress::getStartSavingUserReadProgressAfterDelay();
                },
                'save_user_audio_read_pregress_timeout_in_seconds' => function () {
                    return AudioReadProgress::getSaveUserReadProgressStep();
                },
            ],
        ];
    }
}
