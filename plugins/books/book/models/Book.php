<?php

namespace Books\Book\Models;

use Books\Book\Classes\Converters\BaseConverter;
use Books\Book\Classes\DownloadService;
use Books\Book\Classes\Enums\AgeRestrictionsEnum;
use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Enums\ElectronicFormats;
use Books\Book\Classes\Enums\StatsEnum;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Classes\Rater;
use Books\Book\Classes\Reader;
use Books\Book\Classes\ScopeToday;
use Books\Catalog\Models\Genre;
use Books\Collections\Models\Lib;
use Books\Profile\Models\Profile;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kirschbaum\PowerJoins\PowerJoins;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Collection;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\BelongsToMany;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Database\Relations\HasOneThrough;
use October\Rain\Database\Traits\Validation;
use October\Rain\Support\Facades\Html;
use RainLab\Notify\Models\Notification;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use System\Models\File;
use WordForm;

/**
 * Book Model
 *
 * @method HasOne author
 * @method HasOne advert
 *
 * @property Advert advert
 * @property  Author author
 *
 * @method HasMany editions
 * @method BelongsTo cycle
 *
 * @property  Cycle cycle
 *
 * @method BelongsToMany tags
 * @method BelongsToMany genres
 * @method HasMany authors
 * @method HasMany awards
 * @method HasMany libs
 * @method BelongsToMany coauthors
 * @method BelongsToMany profiles
 * @method HasOneThrough profile
 *
 * @property  Profile profile
 *
 * @method HasOne ebook
 * @method HasOne audiobook
 *
 * @property  Edition ebook
 * @property  Edition audiobook
 *
 * @method AttachOne cover
 *
 * @property  File cover
 * @property  Stats stats
 * @property  BookStatus status
 */
class Book extends Model
{
    use Validation;
    use HasFactory;
    use HasRelationships;
    use PowerJoins;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_book_books';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    public static array $endingArray = ['Книга', 'Книги', 'Книг'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'title',
        'annotation',
        'age_restriction',
        'cycle_id',
        'recommend',
        'h1',
        'meta_title',
        'meta_desc',
        'description',
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'required|between:2,100',
        'annotation' => 'nullable|string',
        'cover' => 'nullable|image|mimes:jpg,jpeg,png|max:3072|dimensions:min_width=104,min_height=150',
        'cycle_id' => 'nullable|integer|exists:books_book_cycles,id',
        'h1' => 'nullable|string|max:255',
        'meta_title' => 'nullable|string|max:255',
        'meta_desc' => 'nullable|string|max:255',
        'description' => 'nullable|string',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'age_restriction' => AgeRestrictionsEnum::class,
    ];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

    /**
     * @var array appends attributes to the API representation of the model (ex. toArray())
     */
    protected $appends = [

    ];

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
        'sales_at',
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [
        'author' => [Author::class, 'key' => 'book_id', 'otherKey' => 'id', 'scope' => 'owner'],
        'ebook' => [Edition::class, 'key' => 'book_id', 'otherKey' => 'id', 'scope' => 'ebook'],
        'audiobook' => [Edition::class, 'key' => 'book_id', 'otherKey' => 'id', 'scope' => 'audio'],
        'stats' => [Stats::class, 'key' => 'book_id', 'otherKey' => 'id'],
        'advert' => [Advert::class, 'key' => 'book_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [
        'authors' => [Author::class, 'key' => 'book_id', 'otherKey' => 'id'],
        'coauthors' => [Author::class, 'key' => 'book_id', 'otherKey' => 'id', 'scope' => 'coAuthors'],
        'editions' => [Edition::class, 'key' => 'book_id', 'id'],
        'libs' => [Lib::class, 'key' => 'book_id', 'otherKey' => 'id'],
        'awards' => [AwardBook::class, 'key' => 'book_id', 'otherKey' => 'id'],
        'bookGenre' => [BookGenre::class, 'key' => 'book_id', 'otherKey' => 'id'],
    ];

    public $belongsTo = [
        'cycle' => [Cycle::class],
    ];

    //TODO ??
    public $hasOneThrough = [
        'profile' => [
            Profile::class,
            'key' => 'book_id',
            'through' => Author::class,
            'throughKey' => 'id',
            'otherKey' => 'id',
            'secondOtherKey' => 'profile_id',
        ],
    ];

    public $belongsToMany = [
        'genres' => [
            Genre::class,
            'table' => 'books_book_genre',
            'pivotModel' => BookGenre::class,
            'key' => 'book_id',
            'otherKey' => 'genre_id',
            'pivot' => ['rate_number'],
        ],
        'tags' => [
            Tag::class,
            'table' => 'books_book_tag',
            'key' => 'book_id',
            'otherKey' => 'tag_id',
            'scope' => 'orderByName',
        ],
        'profiles' => [
            Profile::class,
            'table' => 'books_book_authors',
            'key' => 'book_id',
            'otherKey' => 'profile_id',
            'pivot' => ['percent', 'sort_order', 'is_owner'],
            'pivotSortable' => 'is_owner',
        ],
    ];

    public $morphTo = [];

    public $morphOne = [];

    public $morphMany = [
        'notifications' => [
            Notification::class,
            'name' => 'notifiable',
        ],
        'promocodes' => [
            Promocode::class,
            'name' => 'promoable',
        ],
    ];

    public $attachOne = [
        'cover' => File::class,
    ];

    public $attachMany = [];

    public function reader()
    {
        return new Reader($this, ...func_get_args());
    }

    public function downloadService(): DownloadService
    {
        return new DownloadService($this, ...func_get_args());
    }

    public function pdfConverter(){
        return ElectronicFormats::PDF->converter($this);
    }

    public static function findForPublic(int $book_id, User $user = null)
    {
        return Book::query()->public()->find($book_id) // открыта в публичной зоне
            ?? $user?->profile->books()->whereHas('editions')->find($book_id) // пользователь автор книги
            ?? ($user ? Book::query()
                ->whereHas('editions', fn ($edition) => $edition->whereHas('customers', fn ($customers) => $customers->where('user_id', $user->id)))
                ->find($book_id)
                : null); // пользователь купил книгу
    }
    public function awardsItems()
    {
        return $this->hasManyDeepFromRelations($this->awards(), (new AwardBook())->award());
    }

    public function scopeWithSumAwardItems(Builder $builder, int $ofLastDays = null)
    {
        return $builder->withSum(['awardsItems' => fn ($awards) => $awards->when($ofLastDays, fn ($b) => $b->ofLastDays($b))], 'rate');
    }

    public function scopeOrderByAuthorSortOrder(Builder $builder)
    {
        return $builder
            ->with('authors')
            ->orderBy(Author::make()->qualifyColumn('sort_order'),'desc');
    }

    public function scopeOrderByPopularGenres(Builder $builder)
    {
        return $builder->orderByPowerJoinsMin('bookGenre.rate_number');
    }

    public function scopeOrderByGenresRate(Builder $builder, Genre ...$genres)
    {
        if (! count($genres)) {
            return $builder;
        }
        $builder->withAvg(['bookGenre as genres_rate' => fn ($g) => $g->whereIn('genre_id', array_pluck($genres, 'id'))], 'rate_number');
        $builder->orderByRaw('-genres_rate desc');

        return $builder;
    }

    public function rater(): Rater
    {
        return new Rater($this, ...func_get_args());
    }

    public function paginationTrackers(): HasManyDeep
    {
        return $this->hasManyDeepFromRelationsWithConstraints(
            [$this, 'pagination'],
            [new Pagination(), 'trackers']
        )->withoutGlobalScope(new ScopeToday());
    }

    public function chaptersTrackers(): HasManyDeep
    {
        return $this->hasManyDeepFromRelationsWithConstraints(
            [$this, 'chapters'],
            [new Chapter(), 'trackers']
        );
    }

    public function trackers(): HasManyDeep
    {
        return $this->hasManyDeepFromRelationsWithConstraints(
            [$this, 'editions'],
            [new Edition(), 'trackers']
        );
    }

    public function pagination(): HasManyDeep
    {
        return $this->hasManyDeepFromRelationsWithConstraints(
            [$this, 'chapters'],
            [new Chapter(), 'pagination'],
        );
    }

    public function chapters(): HasManyDeep
    {
        return $this->hasManyDeepFromRelationsWithConstraints(
            [$this, 'editions'],
            [new Edition(), 'chapters']
        );
    }

    /**
     * Аккаунты, у которых есть книга (включая покупки по промокоду)
     */
    public function customers(): HasManyDeep
    {
        return $this->hasManyDeepFromRelationsWithConstraints(
            [$this, 'editions'],
            [new Edition(), 'customers']
        );
    }

    /**
     * Записи для статистики коммерческого кабинета, т.е. только те где книгу купили за деньги
     */
    public function sells(): HasManyDeep
    {
        return $this->hasManyDeepFromRelationsWithConstraints(
            [$this, 'editions'],
            [new Edition(), 'sells']
        );
    }

    public function isAuthor(Profile $profile)
    {
        return $this->authors()->where('profile_id', $profile->id)->exists();
    }

    public function isCommentAllowed(User $user = null): bool
    {
        if ($this->ebook()->value('comment_allowed')) {
            return true;
        }
        $user ??= Auth::getUser();

        return $user && $this->profiles()->user($user)->exists();
    }

    public function scopeCustomersExists(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->has('customers');
    }

    public function scopeSellsExists(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->has('sells');
    }

    public function scopeOrderByBestSells(Builder $builder, bool $asc = false)
    {
        return $builder->orderByLeftPowerJoinsCount('editions.sells.id', $asc ? 'asc' : 'desc');
    }

    public function scopeWithCountEditionSells(Builder $builder, ?Closure $callback): Builder
    {
        return $builder->withCount(['sells' => $callback ?? fn ($b) => $b]);
    }

    public function scopeWithReadTime(Builder $builder, Closure $callback = null): Builder
    {
        return $builder->withSum(['paginationTrackers' => fn ($trackers) => $trackers->when($callback, $callback)], 'time');
    }

    public function scopeWithReadChaptersTrackersCount(Builder $builder, Closure $callback = null): Builder
    {
        return $builder->withCount(['chaptersTrackers' => fn ($trackers) => $trackers
            ->withoutTodayScope()
            ->completed()
            ->when($callback, $callback)]);
    }

    public function scopeWithLastLengthUpdate(Builder $builder): Builder
    {
        return $builder->with(['editions' => fn ($editions) => $editions->withLastLengthRevision()]);
    }

    public function scopeType(Builder $builder, ?EditionsEnums $type): Builder|\Illuminate\Database\Eloquent\Builder
    {
        if (! $type) {
            return $builder;
        }

        return $builder->whereHas('editions', fn ($e) => $e->type($type));
    }

    public function scopeRecommend(Builder $builder, ?bool $value = true): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->where('recommend', $value);
    }

    public function scopeMinPrice(Builder $builder, ?int $price): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('editions', fn ($e) => $e->minPrice($price));
    }

    public function scopeMaxPrice(Builder $builder, ?int $price): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('editions', fn ($e) => $e->maxPrice($price));
    }

    public function scopeFree(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('editions', fn ($e) => $e->free());
    }

    public function scopeNotFree(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('editions', fn ($e) => $e->free(false));
    }

    public function scopeComplete(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('editions', fn ($e) => $e->status(BookStatus::COMPLETE));
    }

    public function scopeEditionTypeIn(Builder $builder, EditionsEnums ...$type): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('editions', fn ($query) => $query->whereIn('type', $type));
    }

    public function scopeDiffWithUnloved(Builder $builder, User $user = null)
    {
        $user ??= Auth::getUser();

        return $builder->hasGenres($user?->unloved_genres ?? getUnlovedFromCookie(), 'exclude');
    }

    public function scopeHasGenres(Builder $builder, ?array $ids, $mode = 'include'): Builder|\Illuminate\Database\Eloquent\Builder
    {
        if ($ids === null || ! count($ids)) {
            return $builder;
        }

        return $builder->{$mode == 'include' ? 'whereHas' : 'whereDoesntHave'}('genres',
            fn ($genres) => $genres->where(fn ($q) => $q->whereIn((new Genre())->getQualifiedKeyName(), $ids))
                ->orWhereIn((new Genre())->qualifyColumn('parent_id'), $ids));
    }

    public function scopeHasTags(Builder $builder, ?array $ids, $mode = 'include'): Builder|\Illuminate\Database\Eloquent\Builder
    {
        if ($ids === null || ! count($ids)) {
            return $builder;
        }

        return $builder->{$mode == 'include' ? 'whereHas' : 'whereDoesntHave'}('tags',
            fn ($tags) => $tags->whereIn('id', $ids));
    }

    public function scopeSearchByString(Builder $query, string $string)
    {
        return $query->public()->where('title', 'like', "%$string%");
    }

    public function isAdult(): bool
    {
        return $this->age_restriction === AgeRestrictionsEnum::A18 || $this->genres->where('adult', true)->count();
    }

    public function scopeAdult(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        if (! shouldRestrictAdult()) {
            return $builder;
        }

        return $builder->where('age_restriction', '<', '18')
            ->whereDoesntHave('genres', fn ($genres) => $genres->adult());
    }

    public function scopePublic(Builder $q)
    {
        return $q->withoutProhibited()
            ->hasProhibitedGenres(has: false)
            ->notEmptyEdition()
            ->onlyPublicStatus()
            //->adult()
            ->genresExists();
    }

    public function isProhibited(): bool
    {
        return (bool) static::query()->prohibitedOnly()->orWhere(fn ($b) => $b->hasProhibitedGenres(true))->find($this->id);
    }

    public function scopeGenresExists(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->has('genres');
    }

    public function scopeHasProhibitedGenres(Builder $builder, bool $has = false)
    {
        return $builder->{$has ? 'whereHas' : 'whereDoesntHave'}('genres', fn ($genres) => $genres->prohibitedOnly());
    }

    public function scopeOnlyPublicStatus(Builder $q): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $q->whereHas('editions', function ($query) {
            return $query->whereIn('status', [BookStatus::COMPLETE->value, BookStatus::WORKING->value, BookStatus::FROZEN->value]);
        });
    }

    public function scopeNotEmptyEdition(Builder $q): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $q->whereHas('editions', function ($query) {
            return $query->notEmpty();
        });
    }

    public function scopeDefaultEager(Builder $q): Builder
    {
        return $q->with([
                'cover',
                'tags',
                'genres' => fn ($q) => $q->withPivot(['rate_number']),
                'stats',
                'ebook' => fn ($ebook) => $ebook->withActiveDiscountExist(),
                'ebook.discount',
                'audiobook' => fn ($audiobook) => $audiobook->withActiveDiscountExist(),
                'audiobook.discount',
                'author.profile',
                'authors.profile',
            ])
            ->inLibExists()
            ->likeExists()
            ->distinct($this->qualifyColumn('id'));
    }

    public function scopeAllowedForDiscount(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('editions', fn ($editions) => $editions->allowedForDiscount());
    }

    public function scopeActiveDiscountExist(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('editions', fn ($editions) => $editions->activeDiscountExist());
    }

    public function scopeWithChapters(Builder $builder): Builder
    {
        return $builder->with(['ebook.chapters' => fn ($i) => $i->public()->withLength()]);
    }

    public function scopeAfterPublishedAtDate(Builder $builder, Carbon|int $date): Builder
    {
        if (is_int($date)) {
            $date = Carbon::now()->copy()->subDays($date);
        }

        return $builder->whereHas('editions', fn ($editions) => $editions->whereDate(Edition::make()->qualifyColumn('sales_at'), '>=', $date));
    }

    public function scopeLikesCount(Builder $builder, Closure $callback = null): Builder
    {
        return $builder->withCount(['favorites as likes_count' => fn ($f) => $f->when($callback, $callback)]);
    }

    public function scopeInLibCount(Builder $builder, Closure $callback = null): Builder
    {
        return $builder->withCount(['libs as in_lib_count' => fn ($libs) => $libs->notWatched()->when($callback, $callback)]);
    }

    public function scopeLikeExists(Builder $builder, User $user = null): Builder
    {
        $user ??= Auth::getUser();

        return $builder->withExists(['favorites as user_liked' => fn ($favorites) => $favorites->user($user)]);
    }

    public function scopeInLibExists(Builder $builder, User $user = null): Builder
    {
        $user ??= Auth::getUser();

        return $builder->withExists(['libs as in_user_lib' => fn ($libs) => $libs->notWatched()->whereHas(
            'favorites', fn ($favorites) => $favorites->user($user)
        )]);
    }

    public function scopeWithProgress(Builder $builder, User $user): Builder
    {
        return $builder->with(['editions' => fn ($edition) => $edition->withProgress($user)]);
    }

    public function getCollectedRate(WidgetEnum $widget)
    {
        return match ($widget) {
            WidgetEnum::hotNew, WidgetEnum::gainingPopularity => $this->stats->{$widget->value}($this->status === BookStatus::WORKING),
            default => 0
        };
    }

    public function orderedAuthors(): Collection
    {
        return $this->authors
            ->sortByDesc('percent')
            ->sortByDesc('is_owner');
    }

    protected function afterCreate(): void
    {
        $this->setDefaultCover();
        // $this->setDefaultEdition();
        $this->stats()->add(new Stats());
        $this->advert()->create();
    }

    public function scopeOrderByDiscountAmount(Builder $builder, bool $asc = false)
    {
        return $builder->orderByPowerJoins('editions.discount.amount', $asc ? 'asc' : 'desc');
    }

    public function scopeOrderBySalesAt(Builder $builder, bool $asc = false)
    {
        return $builder->orderByPowerJoins('editions.sales_at', $asc ? 'asc' : 'desc');
    }

    public function scopeSortByStatValue(Builder $builder, StatsEnum $stat, bool $asc = false)
    {
        return $builder->orderByPowerJoins('stats.'.$stat->mapStatAttribute(), $asc ? 'asc' : 'desc');
    }

    public function refreshAllowedVisits(): int
    {
        $sold_count = $this->editions()->withSellsCount()->pluck('sells_count')->sum();
        $additional_visits = match ((int) $sold_count) {
            0 => 0,
            1 => 350,
            50, 100 => 250,
            500, 1000 => 2000,
            default => (($sold_count % 1500) === 0 ? 1000 : 0)
        };
        $this->advert()->increment('allowed_visit_count', $additional_visits);

        return $additional_visits;
    }

    public function createEventHandler(): void
    {
        $this->setAdultIfHasOne();
        $this->setSortOrder();
    }

    protected function beforeUpdate(): void
    {
        $this->setAdultIfHasOne();
    }

    public function setAdultIfHasOne(): void
    {
        if ($this->genres()->adult()->exists()) {
            $this->age_restriction = AgeRestrictionsEnum::A18;
        }
    }

    /**
     * Try set default book cover if not exists one.
     */
    protected function setDefaultCover(): void
    {
        if (! $this->cover()->exists()) {
            if ($dir = config('book.book_cover_blank_dir')) {
                if (file_exists($file_src = collect(glob(sprintf('%s/%s/*.png', base_path(), $dir)))->random())) {
                    $file = File::make(['is_public' => true])->fromFile($file_src, 'cover.png');
                    $file->save();
                    $this->cover()->add($file);
                }
            }
        }
    }

    /**
     * @deprecated
     *
     * Убрали автоматическое создание Электронного издания при создании книги
     *  после добавления функционала аудиокниг - 16.01.2024
     *
     * @return void
     */
    protected function setDefaultEdition(): void
    {
        if (! $this->ebook()->exists()) {
            $this->editions()->save(Edition::make(['type' => EditionsEnums::default()]));
        }
    }

    public function setSortOrder()
    {
        $this->authors()->each(function ($author) {
            if (! $author->sort_order) {
                $author->update(['sort_order' => ($author->profile->authorships()->max('sort_order') ?? 0) + 1]);
            }
        });
    }

    public static function wordForm(): WordForm
    {
        return new WordForm(...self::$endingArray);
    }

    public function getDeferred($key): Collection
    {
        return $this->getDeferredBindingRecords($key);
    }

    public function getDeferredAuthors($key): Collection
    {
        return $this->getDeferred($key)->where('master_field', '=', 'profiles');
    }

    public function getDeferredAuthor($key, int|Profile $profile)
    {
        return $this->getDeferredAuthors($key)
            ?->first(fn ($bind) => $bind->slave_id == (is_int($profile) ? $profile : $profile->id))
            ?? null;
    }

    public function recommend()
    {
        $this->recommend = true;
        $this->save();
    }

    public function unrecommend()
    {
        $this->recommend = false;
        $this->save();
    }

    public function buyAward(Award $award, User $user = null): ?\Illuminate\Database\Eloquent\Model
    {
        $user ??= Auth::getUser();
        if (! $user) {
            return null;
        }

        return $this->awards()->create([
            'user_id' => $user->id,
            'award_id' => $award->id,
        ]);
    }

    public function getAnnotationShortAttribute(): string
    {
        return Html::limit($this->annotation ?? '', config('books.book::config.annotation_length', 300), '...');
    }

    public function getProfileNamesAttribute(): string
    {
        return implode(', ', $this->authors()->with('profile')->get()->pluck('profile.username')->toArray());
    }

    public function isWorking(): bool
    {
        return $this->status === BookStatus::WORKING;
    }
}
