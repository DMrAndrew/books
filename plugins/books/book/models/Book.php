<?php namespace Books\Book\Models;

use Model;
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

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'title',
        'annotation',
        'user_id',
        'cycle_id',
        'age_restriction',
        'download_allowed',
        'comment_allowed',
        'sales_free',
        'sort_order',
        'free_parts',
        'status',
        'price'
    ];


    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'required|between:2,100',
        'annotation' => 'nullable|string',
        'cover' => 'nullable|image',
        'user_id' => 'required|exists:users,id',
        'price' => 'nullable|integer',
        'free_parts' => 'nullable|integer',
        'download_allowed' => 'nullable|boolean',
        'comment_allowed' => 'nullable|boolean',
        'sales_free' => 'nullable|boolean',
        'fb2' => ['nullable','file', 'mimes:xml'],
        'cycle_id' => 'nullable|integer'
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

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

    public function getPriceAttribute($value): float|int|null
    {
        return $value ? (int)$value / 1000 : (int)$value;
    }

    public function setPriceAttribute(int|string|null $value)
    {
        $this->attributes['price'] = $value ? (int)$value * 1000 : $value;
    }

    public function getStatusAttribute($status): ?BookStatus
    {
        return BookStatus::tryFrom($status);
    }

    public function setStatusAttribute(string|BookStatus $status)
    {
        $this->attributes['status'] = is_string($status) ? $status : $status->value;
    }

    public function getDeffered($key): Collection
    {
        return $this->getDeferredBindingRecords($key);
    }

    public function getAgeRestrictionAttribute($value): ?AgeRestrictionsEnum
    {
        return AgeRestrictionsEnum::tryFrom($value) ?? AgeRestrictionsEnum::default();
    }

    public function setAgeRestrictionAttribute(string|int|AgeRestrictionsEnum $ageRestrictions)
    {
        $this->attributes['age_restriction'] = ((is_string($ageRestrictions) || is_int($ageRestrictions)) ? AgeRestrictionsEnum::tryFrom($ageRestrictions) ?? AgeRestrictionsEnum::default() : $ageRestrictions)->value;
    }

    public function getSalesAtAttribute()
    {
        return $this->chapters()->first()?->published_at ?? null;
    }

    public function getRealPriceAttribute()
    {
        return !!$this->sales_free ? 0 : $this->price;
    }
}
