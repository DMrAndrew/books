<?php

namespace Books\Book\Models;

use Books\Book\Classes\Enums\AgeRestrictionsEnum;
use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Classes\Rater;
use Books\Book\Classes\ScopeToday;
use Books\Catalog\Models\Genre;
use Books\Collections\Models\Lib;
use Books\Profile\Models\Profile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 *
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
 * @method HasMany libs
 * @method BelongsToMany coauthors
 * @method BelongsToMany profiles
 * @method HasOneThrough profile
 *
 * @property  Profile profile
 *
 * @method HasOne ebook
 *
 * @property  Edition ebook
 *
 * @method AttachOne cover
 *
 * @property  File cover
 * @property  Stats stats
 */
class Book extends Model
{
    use Validation;
    use HasFactory;
    use HasRelationships;

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
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'required|between:2,100',
        'annotation' => 'nullable|string',
        'cover' => 'nullable|image',
        'cycle_id' => 'nullable|integer|exists:books_book_cycles,id',
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
        'sales_at',
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [
        'author' => [Author::class, 'key' => 'book_id', 'otherKey' => 'id', 'scope' => 'owner'],
        'ebook' => [Edition::class, 'key' => 'book_id', 'otherKey' => 'id', 'scope' => 'ebook'],
        'stats' => [Stats::class, 'key' => 'book_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [
        'authors' => [Author::class, 'key' => 'book_id', 'otherKey' => 'id'],
        'coauthors' => [Author::class, 'key' => 'book_id', 'otherKey' => 'id', 'scope' => 'coAuthors'],
        'editions' => [Edition::class, 'key' => 'book_id', 'id'],
        'libs' => [Lib::class, 'key' => 'book_id', 'otherKey' => 'id'],
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

    public $morphMany = [];

    public $attachOne = [
        'cover' => File::class,
    ];

    public $attachMany = [];

    public static function sortCollectionBySalesAt($collection, bool $desc = true)
    {
        return $collection->{'sortBy'.($desc ? 'Desc' : '')}(fn ($b) => $b->ebook->sales_at);
    }

    public static function sortCollectionByPopularGenre($collection)
    {
        return $collection->sortBy(fn ($book) => $book->genres->pluck('pivot')->min('rate_number') ?: 10000);
    }

    public function rater(): Rater
    {
        return new Rater($this);
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

    public function isAuthor(User $user)
    {
        return $this->profiles()->user($user)->exists();
    }

    public function isCommentAllowed(?User $user = null): bool
    {
        if ($this->ebook()->value('comment_allowed')) {
            return true;
        }
        $user ??= Auth::getUser();

        return $user && $this->profiles()->user($user)->exists();
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
        return $builder->whereHas('editions', fn ($e) => $e->free(false)->minPrice($price));
    }

    public function scopeMaxPrice(Builder $builder, ?int $price): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('editions', fn ($e) => $e->free(false)->maxPrice($price));
    }

    public function scopeFree(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('editions', fn ($e) => $e->free());
    }

    public function scopeComplete(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('editions', fn ($e) => $e->status(BookStatus::COMPLETE));
    }

    public function scopeHasGenres(Builder $builder, array $ids, $mode = 'include'): Builder|\Illuminate\Database\Eloquent\Builder
    {
        if (! count($ids)) {
            return $builder;
        }

        return $builder->{$mode == 'include' ? 'whereHas' : 'whereDoesntHave'}('genres',
            fn ($genres) => $genres->where(fn ($q) => $q->whereIn('id', $ids))->orWhereIn('parent_id', $ids));
    }

    public function scopeHasTags(Builder $builder, array $ids, $mode = 'include'): Builder|\Illuminate\Database\Eloquent\Builder
    {
        if (! count($ids)) {
            return $builder;
        }

        return $builder->{$mode == 'include' ? 'whereHas' : 'whereDoesntHave'}('tags',
            fn ($tags) => $tags->whereIn('id', $ids));
    }

    public function scopeSearchByString(Builder $query, string $string)
    {
        return $query->public()->where('title', 'like', "%$string%");
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
        return $q->notEmptyEdition()->onlyPublicStatus()->adult();
    }

    public function scopeOnlyPublicStatus(Builder $q): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $q->whereHas('editions', function ($query) {
            return $query->whereNotIn('status', [BookStatus::HIDDEN->value]);
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
        return $q->with(['cover', 'tags', 'genres', 'stats', 'ebook', 'author.profile', 'authors.profile'])
            ->inLibExists()
            ->likeExists();
    }

    public function scopeWithChapters(Builder $builder): Builder
    {
        return $builder->with(['ebook.chapters' => fn ($i) => $i->published()]);
    }

    public function scopeAfterPublishedAtDate(Builder $builder, Carbon $carbon): Builder
    {
        return $builder->whereHas('editions', fn ($editions) => $editions->whereDate('sales_at', '>=', $carbon));
    }

    public function scopeLikesCount(Builder $builder): Builder
    {
        return $builder->withCount(['favorites as likes_count']);
    }

    public function scopeInLibCount(Builder $builder): Builder
    {
        return $builder->withCount(['libs as in_lib_count' => fn ($libs) => $libs->notWatched()]);
    }

    public function scopeLikeExists(Builder $builder, ?User $user = null): Builder
    {
        $user ??= Auth::getUser();

        return $builder->withExists(['favorites as user_liked' => fn ($favorites) => $favorites->user($user)]);
    }

    public function scopeInLibExists(Builder $builder, ?User $user = null): Builder
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

    protected function afterCreate()
    {
        $this->setDefaultCover();
        $this->setDefaultEdition();
        $this->stats()->add(new Stats());
    }

    public function createEventHandler()
    {
        $this->setAdultIfHasOne();
        $this->setSortOrder();
    }

    public function updateEventHandler()
    {
        $this->setAdultIfHasOne();
    }

    /**
     * Try set default book cover if not exists one.
     *
     * @return void
     */
    protected function setDefaultCover(): void
    {
        if (! $this->cover()->exists()) {
            if ($dir = config('book.book_cover_blank_dir')) {
                $file_src = collect(glob(base_path()."/$dir/*.png"))->random();
                if (file_exists($file_src)) {
                    $file = (new File())->fromFile($file_src, 'cover.png');
                    $file->is_public = true;
                    $file->save();
                    $this->cover()->add($file);
                }
            }
        }
    }

    protected function setDefaultEdition(): void
    {
        if (! $this->ebook()->exists()) {
            $this->editions()->save(new Edition(['type' => EditionsEnums::default()->value]));
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

    public function setAdultIfHasOne()
    {
        if ($this->genres()->adult()->exists()) {
            $this->update(['age_restriction' => AgeRestrictionsEnum::A18]);
        }
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
}
