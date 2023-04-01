<?php

namespace Books\Profile\Models;

use Books\Book\Models\Author;
use Books\Book\Models\Book;
use Books\Book\Models\Cycle;
use Books\Profile\Classes\ProfileService;
use Books\Profile\Factories\ProfileFactory;
use Books\Profile\Traits\Subscribable;
use Books\User\Classes\PrivacySettingsEnum;
use Books\User\Classes\UserSettingsEnum;
use Books\User\Models\Settings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\BelongsToMany;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Traits\Revisionable;
use October\Rain\Database\Traits\Validation;
use October\Rain\Resize\Resizer;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use System\Models\File;
use System\Models\Revision;
use ValidationException;
use WordForm;

/**
 * Profile Model
 *
 * @method BelongsToMany books
 * @method BelongsToMany subscribers
 * @method BelongsToMany subscriptions
 * @method HasMany authorships
 * @method AttachOne banner
 * @method AttachOne avatar
 */
class Profile extends Model
{
    use Validation;
    use Revisionable;
    use Subscribable;
    use HasFactory;

    const MAX_USER_PROFILES_COUNT = 5;

    public static string $factory = ProfileFactory::class;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_profile_profiles';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    protected $revisionable = ['username'];

    public static array $endingArray = ['Автор', 'Автора', 'Авторов'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'username',
        'username_clipboard',
        'username_clipboard_comment',
        'status',
        'about',
        'avatar',
        'banner',
        'ok',
        'phone',
        'tg',
        'vk',
        'email',
        'website',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'username' => 'required|between:2,255',
        'username_clipboard' => 'nullable|between:2,255',
        'username_clipboard_comment' => 'nullable|string',
        'avatar' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|dimensions:min_width=168,min_height=168|max:2048',
        'banner' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:4096',
        'status' => 'nullable|string',
        'about' => 'nullable|string',
        'website' => 'nullable|url',
        'email' => 'nullable|email',
        'phone' => 'nullable|string',
        'tg' => 'nullable|url',
        'ok' => 'nullable|url',
        'vk' => 'nullable|url',
    ];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

    /**
     * @var array appends attributes to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array hidden attributes removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [

    ];

    public $hasMany = [
        'authorships' => [Author::class, 'key' => 'profile_id', 'otherKey' => 'id'],
        'settings' => [Settings::class, 'key' => 'user_id', 'otherKey' => 'user_id'],
        'cycles' => [Cycle::class, 'key' => 'user_id', 'otherKey' => 'id'],
    ];

    public $belongsTo = ['user' => User::class, 'key' => 'id', 'otherKey' => 'user_id'];

    public $belongsToMany = [
        'books' => [
            Book::class,
            'table' => 'books_book_authors',
            'key' => 'profile_id',
            'otherKey' => 'book_id',
            'pivot' => ['percent', 'sort_order', 'is_owner'],
        ],
        'subscribers' => [Profile::class, 'table' => 'books_profile_subscribers', 'key' => 'profile_id', 'otherKey' => 'subscriber_id'],
        'subscriptions' => [Profile::class, 'table' => 'books_profile_subscribers', 'key' => 'subscriber_id', 'otherKey' => 'profile_id'],
    ];

    public $morphTo = [];

    public $morphOne = [];

    public $morphMany = [
        'revision_history' => [Revision::class, 'name' => 'revisionable'],
    ];

    public $attachOne = [
        'banner' => [File::class],
        'avatar' => [File::class],
    ];

    public $attachMany = [];

    public function service(): ProfileService
    {
//        (new Resizer())->resize(1152, 160)->save();

        return new ProfileService($this);
    }

    public function isCommentAllowed(?Profile $profile = null)
    {
        $profile ??= Auth::getUser()?->profile;
        if (! $profile) {
            return false;
        }
        if ($profile->is($this)) {
            return true;
        }
        $setting = $this->settings()->type(UserSettingsEnum::PRIVACY_ALLOW_FIT_ACCOUNT_INDEX_PAGE)->first();
        if (! $setting) {
            return false;
        }

        return match (PrivacySettingsEnum::tryFrom($setting->value)) {
            PrivacySettingsEnum::ALL => true,
            PrivacySettingsEnum::SUBSCRIBERS => $profile->hasSubscription($this),
            default => false
        };
    }

    public function canSeeCommentFeed(?Profile $profile = null)
    {
        $profile ??= Auth::getUser()?->profile;
        if (! $profile) {
            return false;
        }
        if ($profile->is($this)) {
            return true;
        }
        $setting = $this->settings()->type(UserSettingsEnum::PRIVACY_ALLOW_VIEW_COMMENT_FEED)->first();
        if (! $setting) {
            return false;
        }

        return match (PrivacySettingsEnum::tryFrom($setting->value)) {
            PrivacySettingsEnum::ALL => true,
            PrivacySettingsEnum::SUBSCRIBERS => $profile->hasSubscription($this),
            default => false
        };
    }

    public function scopeShortPublicEager(Builder $builder)
    {
        return $builder->booksCount()->withSubscriberCount()->with(['avatar']);
    }

    public function scopeBooksExists(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('books', fn ($book) => $book->public());
    }

    public function scopeBooksCount(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->withCount(['books' => fn ($book) => $book->public()]);
    }

    public function getIsCurrentAttribute(): bool
    {
        return $this->user->current_profile_id == $this->id;
    }

    public function acceptClipboardUsername()
    {
        $this->service()->replaceUsernameFromClipboard();
    }

    public function rejectClipboardUsername()
    {
        $this->service()->replaceUsernameFromClipboard(reject: true);
    }

    public function getFirstLatterAttribute(): string
    {
        return strtoupper(mb_substr($this->attributes['username'], 0, 1));
    }

    public function scopeSearchByString(Builder $query, string $string): Builder
    {
        return $query->usernameLike($string)->orWhere('id', '=', $string);
    }

    public function scopeUsernameLike(Builder $builder, string $username): Builder
    {
        return $builder->where('username', 'like', "%$username%");
    }

    public function scopeUsername(Builder $builder, string $username): Builder
    {
        return $builder->where('username', '=', $username);
    }

    public function scopeUsernameClipboard(Builder $builder, string $string): Builder
    {
        return $builder->where('username_clipboard', '=', $string);
    }

    public function scopeUser(Builder $builder, User $user): Builder
    {
        return $builder->where('user_id', '=', $user->id);
    }

    public function isEmpty(): bool
    {
        return ! collect($this->only(['avatar', 'banner', 'status', 'about']))->some(fn ($i) => (bool) $i);
    }

    public function isContactsEmpty(): bool
    {
        return ! collect($this->only(['ok', 'phone', 'tg', 'vk', 'email', 'website']))->some(fn ($i) => (bool) $i);
    }

    public static function wordForm(): WordForm
    {
        return new WordForm(...self::$endingArray);
    }

    public function isUsernameExists(string $string): bool
    {
        return $this->user->profiles()->username($string)->exists() || $this->user->profiles()->usernameClipboard($string)->exists();
    }

    public function maxProfilesCount(): int
    {
        return self::MAX_USER_PROFILES_COUNT;
    }

    protected function beforeCreate()
    {
        if ($this->user->profiles()->count() >= self::MAX_USER_PROFILES_COUNT) {
            throw new ValidationException(['username' => 'Превышен лимит профилей.']);
        }
        if ($this->isUsernameExists($this->username)) {
            throw new ValidationException(['username' => 'Псевдоним уже занят.']);
        }
    }
}
