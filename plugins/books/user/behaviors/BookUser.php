<?php

namespace Books\User\Behaviors;

use Books\Book\Models\Edition;
use Books\Book\Models\UserBook;
use Books\Comments\Models\Comment;
use Books\Orders\Models\Order;
use Books\Profile\Models\OperationHistory;
use Books\Profile\Models\Profile;
use Books\User\Classes\BoolOptionsEnum;
use Books\User\Classes\UserEventHandler;
use Books\User\Classes\UserService;
use Books\User\Classes\UserSettingsEnum;
use Books\User\Models\Settings;
use Books\User\Models\User as BooksUser;
use Carbon\Carbon;
use Exception;
use Log;
use October\Rain\Database\Builder;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;
use ValidationException;

class BookUser extends ExtensionBase
{
    const MIN_BIRTHDAY = '01.01.1940';

    protected array $fillable = [
        'nickname',
        'birthday',
        'show_birthday',
        'country_id',
        'required_post_register',
        'favorite_genres',
        'loved_genres',
        'unloved_genres',
        'exclude_genres',
        'see_adult',
        'asked_adult_agreement',
    ];

    protected array $rules = [];

    protected array $cast = [
        'show_birthday' => 'boolean',
        'see_adult' => 'boolean',
        'asked_adult_agreement' => 'boolean',
        'required_post_register' => 'boolean',
    ];

    protected array $jsonable = [
        'favorite_genres',
        'exclude_genres',
        'loved_genres',
        'unloved_genres',
    ];

    public function __construct(protected User $parent)
    {
        $this->parent->addValidationRule('show_birthday', 'boolean');
        $this->parent->addValidationRule('birthday', 'required');
        $this->parent->addValidationRule('birthday', 'required');
        //        $this->parent->addValidationRule('nickname', 'required'); багует
        $this->parent->addDateAttribute('birthday');
        $this->parent->addFillable($this->fillable);
        $this->parent->addCasts($this->cast);
        $this->parent->addJsonable($this->jsonable);

        $this->bindEvents();
        $this->bindRelations();
        $this->bindCustomMessages();
    }

    protected function bindEvents(): void
    {
        $this->parent->bindEvent('model.afterCreate', fn () => (new UserEventHandler())->afterCreate($this->parent->fresh()));
    }

    protected function bindRelations(): void
    {
        $this->parent->hasMany['comments'] = [Comment::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['settings'] = [Settings::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['operations'] = [
            OperationHistory::class,
            'key' => 'user_id',
            'otherKey' => 'id',
            'order' => 'id desc',
        ];
        $this->parent->hasMany['ownedBooks'] = [UserBook::class];
        $this->parent->hasMany['orders'] = [Order::class];
    }

    protected function bindCustomMessages(): void
    {
        $this->parent->customMessages = array_merge($this->parent->customMessages, [
            'birthday.date' => 'Поле Дата рождения должно быть корректной датой',
            'birthday.required' => 'Поле Дата рождения обязательное для заполнения',
        ]);

    }

    public function service(): UserService
    {
        return new UserService($this->parent);
    }

    public function maxProfilesCount(): int
    {
        return Profile::MAX_USER_PROFILES_COUNT;
    }

    /**
     * @throws ValidationException
     */
    public function setBirthdayAttribute($value): void
    {
        if (! $value) {
            $this->parent->attributes['birthday'] = null;

            return;
        }

        try {
            $date = Carbon::parse($value);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            throw new ValidationException(['birthday' => 'Некорректная дата рождения']);
        }
        $date->lessThan(today()) ?: throw new ValidationException(['birthday' => 'Дата рождения не может быть больше текущего дня']);
        $date->gte(Carbon::parse(self::MIN_BIRTHDAY)) ?: throw new ValidationException(['birthday' => 'Дата рождения не может быть меньше '.self::MIN_BIRTHDAY]);
        $this->parent->attributes['birthday'] = $date;
    }

    public function canSetAdult(): bool
    {
        return $this->parent->birthday && abs(Carbon::now()->diffInYears($this->parent->birthday)) > 17;
    }

    public function allowedSeeAdult(): bool
    {
        return $this->parent->birthday && $this->parent->see_adult;
    }

    public function fetchRequired(): bool
    {
        return $this->requiredPostRegister() || $this->requiredAskAdult();
    }

    public function requiredPostRegister()
    {
        return $this->parent->required_post_register;
    }

    public function requiredAskAdult(): bool
    {
        return $this->parent->asked_adult_agreement == 0 && $this->parent->canSetAdult();
    }

    public function getNameAttribute()
    {
        return $this->parent->username;
    }

    public function scopeUsernameLike($q, $name)
    {
        return $q->whereHas('profiles', fn ($profile) => $profile->usernameLike($name));
    }

    public function scopeUsername($q, $name)
    {
        return $q->whereHas('profiles', fn ($profile) => $profile->username($name));
    }

    public function scopeSettingsEnabledBlogPostNotifications(Builder $builder): Builder
    {
        return $builder
            ->whereDoesntHave('settings', function ($query) {
                $query
                    ->type(UserSettingsEnum::NOTIFY_NEW_RECORD_BLOG)
                    ->valueIs(BoolOptionsEnum::ON);
            });
    }

    public function scopeSettingsEnabledVideoBlogPostNotifications(Builder $builder): Builder
    {
        return $builder
            ->whereDoesntHave('settings', function ($query) {
                $query
                    ->type(UserSettingsEnum::NOTIFY_NEW_RECORD_VIDEO_BLOG)
                    ->valueIs(BoolOptionsEnum::ON);
            });
    }

    public function scopeSettingsEnabledUpdateLibraryItemsNotifications(Builder $builder): Builder
    {
        return $builder
            ->whereDoesntHave('settings', function ($query) {
                $query
                    ->type(UserSettingsEnum::NOTIFY_UPDATE_LIBRARY_ITEMS)
                    ->valueIs(BoolOptionsEnum::ON);
            });
    }

    public function scopeSettingsEnabledBookDiscountNotifications(Builder $builder): Builder
    {
        return $builder
            ->whereDoesntHave('settings', function ($query) {
                $query
                    ->type(UserSettingsEnum::NOTIFY_BOOK_DISCOUNT)
                    ->valueIs(BoolOptionsEnum::ON);
            });
    }

    public function bookIsBought(Edition $edition): bool
    {
        return $edition->isSold($this->parent);
    }

    public function toBookUser()
    {
        return BooksUser::from($this->parent);
    }
}
