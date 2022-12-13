<?php namespace Books\Book\Models;

use Books\Catalog\Models\Genre;
use Model;
use System\Models\File;
use RainLab\User\Models\User;
use October\Rain\Database\Traits\Validation;

/**
 * Book Model
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

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'title',
        'annotation',
        'author_id'
    ];


    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'required|between:2,100',
        'annotation' => 'string',
        'cover' => 'nullable|image',
        'author_id' => 'required|exists:users, id',
        'price' => 'integer',
        'sales_at' => 'date',
        'free_parts' => 'integer'
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
        'updated_at'
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [
        'author' => [User::class, 'key' => 'id', 'otherKey' => 'author_id'],
        'cycle' => [Cycle::class, 'key' => 'id', 'otherKey' => 'cycle_id']
    ];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [
        'genres' => [
            Genre::class,
            'table' => 'books_book_genre',
            'key' => 'book_id',
            'otherKey' => 'genre_id'
        ],
        'tags' => [
            Tags::class,
            'table' => 'books_book_tag',
            'key' => 'book_id',
            'otherKey' => 'tag_id',
            'scope' => 'orderByName'
        ],
        'coauthors' => [
            User::class,
            'pivotModel' => CoAuthor::class,
            'key' => 'book_id',
            'otherKey' => 'author_id',
        ]
    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = ['cover' => File::class];
    public $attachMany = [];

    public function getPriceAttribute(int $value): float|int
    {
        return $value / 1000;
    }

    public function setPriceAttribute(float|int $value): int
    {
        $this->attributes['price'] = $value * 1000;
    }

    public function getStatusAttribute($status): ?BookStatus
    {
        return BookStatus::tryFrom($status);
    }

    public function setStatusAttribute(string|BookStatus $status)
    {
        $this->attributes['status'] = is_string($status) ? $status : $status->value;
    }
}
