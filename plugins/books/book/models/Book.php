<?php namespace Books\Book\Models;

use Db;
use Model;
use October\Rain\Database\Builder;
use System\Models\File;
use RainLab\User\Models\User;
use Books\Catalog\Models\Genre;
use October\Rain\Database\Collection;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\BelongsToMany;

/**
 * Book Model
 *
 * @method AttachOne cover
 * @method AttachOne fb2
 * @method HasOne user
 * @method HasMany chapters
 * @method BelongsTo cycle
 * @method BelongsToMany tags
 * @method BelongsToMany genres
 * @method BelongsToMany coauthors
 */
class Book extends Model
{
    use Sortable;
    use Validation;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_book_books';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    public array $endingArray = ['Книга', 'Книги', 'Книг'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'title',
        'annotation',
        'user_id',
        'age_restriction',
        'download_allowed',
        'comment_allowed',
        'sales_free',
        'sort_order',
        'free_parts',
        'status',
        'price',
        'cycle_id'
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'required|between:2,100',
        'annotation' => 'nullable|string',
        'cover' => 'nullable|image',
        'user_id' => 'required|exists:users,id',
        'price' => 'filled|integer|min:0',
        'free_parts' => 'filled|integer|min:0',
        'download_allowed' => 'boolean',
        'comment_allowed' => 'boolean',
        'sales_free' => 'boolean',
        'fb2' => ['nullable', 'file', 'mimes:xml'],
        'cycle_id' => 'nullable|integer|exists:books_book_cycles,id'
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'free_parts' => 'integer',
        'price' => 'integer',
        'sales_free' => 'boolean',
        'download_allowed' => 'boolean',
        'comment_allowed' => 'boolean',
        'status' => BookStatus::class,
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

    ];
    public $hasMany = [
        'chapters' => [Chapter::class, 'key' => 'book_id', 'otherKey' => 'id']
    ];
    public $belongsTo = [
        'cycle' => [Cycle::class],
        'user' => [User::class],
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
        'coauthors' => [
            User::class,
            'pivotModel' => CoAuthor::class,
            'table' => 'books_book_co_authors',
            'key' => 'book_id',
            'otherKey' => 'user_id',
            'pivot' => ['percent']
        ]
    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [
        'cover' => File::class,
        'fb2' => File::class
    ];
    public $attachMany = [];


    public function getRealPriceAttribute()
    {
        return !!$this->sales_free ? 0 : $this->price;
    }

    public function scopeSearchByString(Builder $query, string $string)
    {
        return $query->public()->where('title', 'like', "%$string%");
    }

    public function scopePublic(Builder $q)
    {
        return $q->whereNotIn('status', [BookStatus::FROZEN->value, BookStatus::HIDDEN->value]);
    }


    public function nextChapterSortOrder()
    {
        return ($this->chapters()->max('sort_order') ?? 0) + 1;
    }

    public function setSalesAt()
    {
        $this->sales_at = $this->chapters()->published(until_now: false)?->min('published_at') ?? null;
        $this->save();
    }

    public function lengthRecount()
    {
        $this->length = (int)$this->chapters()->sum('length') ?? 0;
        $this->save();
    }

    public function changeChaptersOrder(array $ids, ?array $order = null)
    {
        Db::transaction(function () use ($ids, $order) {
            $order ??= $this->chapters()->pluck('sort_order')->toArray();
            $this->chapters()->first()->setSortableOrder($ids, $order);
            $this->setFreeParts();
        });

    }

    public function setFreeParts()
    {
        Db::transaction(function () {
            $this->chapters()->limit($this->free_parts ?? 0)->update(['edition' => ChapterEdition::FREE]);
            $this->chapters->skip($this->free_parts ?? 0)->each(fn($i) => $i->update(['edition' => ChapterEdition::PAY]));
        });
    }

    public function recompute()
    {
        $this->lengthRecount();
        $this->setSalesAt();
        $this->setFreeParts();
    }

    /**
     * Try set default book cover if not exists one.
     *
     * @param Book $book
     * @return void
     */
    protected function setDefaultCover(): void
    {
        if (!$this->cover) {
            if ($dir = config('book.book_cover_blank_dir')) {
                $file_src = collect(glob(base_path() . "/$dir/*.png"))->random();
                if (file_exists($file_src)) {
                    $file = (new File())->fromFile($file_src);
                    $file->is_public = true;
                    $file->save();
                    $this->cover()->add($file);
                }
            }
        }
    }

    protected function afterCreate()
    {
        $this->setDefaultCover();
    }

    public function getDiffered($key): Collection
    {
        return $this->getDeferredBindingRecords($key);
    }

}
