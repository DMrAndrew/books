<?php namespace Books\Book\Models;


use Model;
use System\Models\File;
use Books\Catalog\Models\Genre;
use Books\Profile\Models\Profile;
use October\Rain\Database\Builder;
use October\Rain\Database\Collection;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\BelongsToMany;
use October\Rain\Database\Relations\HasOneThrough;

/**
 * Book Model
 *
 * @method HasOne author
 * @method HasOne ebookEdition
 * @method HasMany editions
 * @method BelongsTo cycle
 * @method BelongsToMany tags
 * @method BelongsToMany genres
 * @method HasMany authors
 * @method BelongsToMany coauthors
 * @method BelongsToMany profiles
 * @method HasOneThrough profile
 * @method HasOneThrough ebook
 * @method AttachOne cover
 */
class Book extends Model
{

    use Validation;

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
        'cycle_id'
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'required|between:2,100',
        'annotation' => 'nullable|string',
        'cover' => 'nullable|image',
        'cycle_id' => 'nullable|integer|exists:books_book_cycles,id'
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
        'sales_at'
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [
        'author' => [Author::class, 'key' => 'book_id', 'otherKey' => 'id', 'scope' => 'owner'],
        'ebookEdition' => [Edition::class, 'key' => 'book_id', 'id', 'scope' => 'ebook'],
    ];
    public $hasMany = [
        'authors' => [Author::class, 'key' => 'book_id', 'otherKey' => 'id'],
        'coauthors' => [Author::class, 'key' => 'book_id', 'otherKey' => 'id', 'scope' => 'notOwner'],
        'editions' => [Edition::class, 'key' => 'book_id', 'id']
    ];
    public $belongsTo = [
        'cycle' => [Cycle::class],
    ];

    public $hasOneThrough = [
        'ebook' => [
            EbookEdition::class,
            'key' => 'book_id',
            'through' => Edition::class,
            'throughKey' => 'id',
            'otherKey' => 'id',
            'secondOtherKey' => 'editionable_id'
        ],
        'profile' => [
            Profile::class,
            'key' => 'book_id',
            'through' => Author::class,
            'throughKey' => 'id',
            'otherKey' => 'id',
            'secondOtherKey' => 'profile_id'
        ]
    ];


    public $belongsToMany = [
        'genres' => [
            Genre::class,
            'table' => 'books_book_genre',
            'key' => 'book_id',
            'otherKey' => 'genre_id'
        ],
        'tags' => [
            Tag::class,
            'table' => 'books_book_tag',
            'key' => 'book_id',
            'otherKey' => 'tag_id',
            'scope' => 'orderByName'
        ],
        'profiles' => [
            Profile::class,
            'table' => 'books_book_authors',
            'key' => 'book_id',
            'otherKey' => 'profile_id',
            'pivot' => ['percent', 'sort_order', 'is_owner'],
            'pivotSortable' => 'is_owner'
        ],

    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [
        'cover' => File::class,

    ];
    public $attachMany = [];


    public function scopeSearchByString(Builder $query, string $string)
    {
        return $query->public()->where('title', 'like', "%$string%");
    }

    public function scopePublic(Builder $q)
    {
        return $q->whereHas('editions', function ($query) {
            return $query->whereHasMorph('editionable', EbookEdition::class, function (Builder $builder) {
                return $builder->whereNotIn('status', [BookStatus::HIDDEN->value]);
            });
        });
    }

    public function scopeDefualtEager(Builder $q)
    {
        return $q->with(['cover', 'tags', 'genres', 'ebook', 'author.profile']);
    }


    /**
     * Try set default book cover if not exists one.
     *
     * @return void
     */
    protected function setDefaultCover(): void
    {
        if (!$this->cover) {
            if ($dir = config('book.book_cover_blank_dir')) {
                $file_src = collect(glob(base_path() . "/$dir/*.png"))->random();
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
        if (!$this->ebook()->exists()) {
            $edition = new Edition();
            $edition->editionable = EbookEdition::create();
            $this->editions()->save($edition);
        }
    }

    public function setSortOrder()
    {
        $this->authors()->each(function ($author) {
            if (!$author->sort_order) {
                $author->update(['sort_order' => ($author->profile->authorships()->max('sort_order') ?? 0) + 1]);
            }
        });
    }


    protected function afterCreate()
    {
        $this->setDefaultCover();
        $this->setDefaultEdition();

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
        return $this->getDeferredAuthors($key)?->first(fn($bind) => $bind->slave_id === (is_int($profile) ? $profile : $profile->id)) ?? null;
    }


}
