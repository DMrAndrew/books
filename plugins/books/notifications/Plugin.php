<?php

namespace Books\Notifications;

use Books\Notifications\Classes\Actions\StoreDatabaseAction;
use Books\Notifications\Classes\Behaviors\NotificationModel;
use Books\Notifications\Classes\Behaviors\NotificationOwner;
use Books\Notifications\Classes\Contracts\NotificationService as NotificationServiceContract;
use Books\Notifications\Classes\Events\AuthorAccepted;
use Books\Notifications\Classes\Events\AuthorInvited;
use Books\Notifications\Classes\Events\BeforeBirthdayMessage;
use Books\Notifications\Classes\Events\BirthdayMessage;
use Books\Notifications\Classes\Events\BookCompleted;
use Books\Notifications\Classes\Events\BookCreated;
use Books\Notifications\Classes\Events\BookSelling;
use Books\Notifications\Classes\Events\BookSellingSubs;
use Books\Notifications\Classes\Events\BookUpdated;
use Books\Notifications\Classes\Events\CommentCreated;
use Books\Notifications\Classes\Events\CommentReplied;
use Books\Notifications\Classes\Events\DeferredApplied;
use Books\Notifications\Classes\Events\DiscountCreated;
use Books\Notifications\Classes\Events\PostPublished;
use Books\Notifications\Classes\Events\SystemMessage;
use Books\Notifications\Classes\Services\NotificationService;
use Books\Notifications\Components\Notifications;
use Books\Notifications\Components\NotificationsInHeader;
use Books\Profile\Models\Profile;
use RainLab\Notify\Classes\Notifier;
use RainLab\Notify\Models\Notification;
use RainLab\Notify\NotifyRules\SaveDatabaseAction;
use RainLab\User\Models\User;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    public $require = [
        'Books.User',
        'Books.Profile',
        'Books.Book',
        'Books.Comments',
        'RainLab.Notify',
    ];

    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails(): array
    {
        return [
            'name' => 'Notifications',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-leaf',
        ];
    }

    /**
     * register method, called when the plugin is first registered.
     */
    public function register(): void
    {
        $this->app->bind(NotificationServiceContract::class, NotificationService::class);
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot(): void
    {
        $this->extendModels();

        /*
         * Compatability with RainLab.Notify
         */
        $this->bindNotificationEvents();
        $this->extendSaveDatabaseAction();
        $this->extendNotificationModel();
    }

    /**
     * @return array
     */
    public function registerNotificationRules(): array
    {
        return [
            'actions' => [
                StoreDatabaseAction::class,
            ],
            'events' => [
                BookCreated::class,
                BookCompleted::class,
                BookSelling::class,
                BookSellingSubs::class,
                AuthorInvited::class,
                AuthorAccepted::class,
                CommentCreated::class,
                CommentReplied::class,
                DiscountCreated::class,
                SystemMessage::class,
                DeferredApplied::class,
                PostPublished::class,
                BirthdayMessage::class,
            ],
            'groups' => [
                'user' => [
                    'label' => 'User',
                    'icon' => 'icon-user',
                ],
            ],
            'presets' => '$/books/notifications/classes/presets/notify.yaml',
        ];
    }

    /**
     * @return string[]
     */
    public function registerComponents(): array
    {
        return [
            Notifications::class => 'Notifications',
            NotificationsInHeader::class => 'NotificationsInHeader',
        ];
    }

    /**
     * @return void
     */
    protected function bindNotificationEvents(): void
    {
        if (!class_exists(Notifier::class)) {
            return;
        }

        Notifier::bindEvents([
            'books.book::book.created' => BookCreated::class,
            //            'books.book::book.updated' => TestEvent::class,
            'books.book::book.updated' => BookUpdated::class,
            'books.book::book.completed' => BookCompleted::class,
            'books.book::book.selling.full' => BookSelling::class,
            'books.book::book.selling.subs' => BookSellingSubs::class,
            'books.book::author.invited' => AuthorInvited::class,
            'books.book::author.accepted' => AuthorAccepted::class,
            'books.comments::comment.created' => CommentCreated::class,
            'books.comments::comment.replied' => CommentReplied::class,
            'books.book::edition.discounted' => DiscountCreated::class,
            'books.book::content.deferred.merged' => DeferredApplied::class,
            'books.book::content.deferred.rejected' => DeferredApplied::class,
            'books.blog::post.published' => PostPublished::class,
            'system::message' => SystemMessage::class,
            'system::birthday' => BirthdayMessage::class,
        ]);
    }

    /**
     * @return void
     */
    protected function extendSaveDatabaseAction(): void
    {
        if (!class_exists(SaveDatabaseAction::class)) {
            return;
        }

        SaveDatabaseAction::extend(static function (SaveDatabaseAction $action) {
            $action->addTableDefinition([
                'label' => 'Аккаунт',
                'class' => User::class,
                'relation' => 'notifications',
                'param' => 'user',
            ]);

            $action->addTableDefinition([
                'label' => 'Профиль',
                'class' => Profile::class,
                'relation' => 'notifications',
                'param' => 'profile',
            ]);
        });
    }

    protected function extendNotificationModel(): void
    {
        Notification::extend(static function (Notification $model): void {
            $model->implementClassWith(NotificationModel::class);
        });
    }

    /**
     * @return void
     */
    protected function extendModels(): void
    {
        User::extend(static function (User $model): void {
            $model->implementClassWith(NotificationOwner::class);
        });

        Profile::extend(static function (Profile $model): void {
            $model->implementClassWith(NotificationOwner::class);
        });
    }
}
