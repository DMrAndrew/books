<?php

namespace Books\Book\Models;

use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;

/**
 * Tags Model
 */
class Tag extends Model
{
    use Validation;

    public const NAME = 'name';

    /**
     * @var string table associated with the model
     */
    public $table = 'books_book_tags';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = ['name'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'name' => 'required|string|min:2|max:50',
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
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [];

    public $hasMany = [];

    public $belongsTo = [];

    public $belongsToMany = [
        'books' => [
            Book::class,
            'table' => 'books_book_tag',
            'key' => 'tag_id',
            'otherKey' => 'book_id',
        ],
    ];

    public $morphTo = [];

    public $morphOne = [];

    public $morphMany = [];

    public $attachOne = [];

    public $attachMany = [];

    public function scopeOrderByName($q)
    {
        return $q->orderBy('name');
    }

    public function scopePublic($q)
    {
        return $q;
    }

    public function scopeNameLike($q, string $name)
    {
        return $q->where('name', 'like', "%$name%");
    }

    public function scopeAsOption(Builder $builder): Builder
    {
        return $builder->select(['id', 'name']);
    }
}
