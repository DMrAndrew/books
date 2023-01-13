<?php namespace Books\Book\Models;

use October\Rain\Database\Pivot;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * CoAuthor Model
 */
class CoAuthor extends Pivot
{
    use Validation;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_book_co_authors';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = ['book_id', 'user_id', 'percent',];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'book_id' => 'required|exists:books_book_books,id',
        'user_id' => 'required|exists:users,id',
        'percent' => 'integer|max:100',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'percent' => 'integer'
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
        'updated_at'
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [
        'book' => [
            Book::class,
            'key' => 'id',
            'otherKey' => 'book_id'
        ],
        'user' => [
            User::class,
            'key' => 'id',
            'otherKey' => 'user_id'
        ]
    ];

    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];


    public function getOwnerAttribute()
    {
        return $this->attributes['owner'] ?? false
        || $this->user_id === $this->book?->user_id ?? false;
    }

}
