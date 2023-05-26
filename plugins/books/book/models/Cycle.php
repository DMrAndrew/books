<?php

namespace Books\Book\Models;

use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;
use Staudenmeir\EloquentHasManyDeep\HasTableAlias;

/**
 * Cycle Model
 *
 * @property $books
 */
class Cycle extends Model
{
    use Validation;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_book_cycles';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = ['name', 'user_id'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'name' => 'required|string|max:64',
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
    public $hasOne = [

    ];

    public $hasMany = [
        'books' => [Book::class],
    ];

    public $belongsTo = [
        'user' => [User::class],
    ];

    public $belongsToMany = [];

    public $morphTo = [];

    public $morphOne = [];

    public $morphMany = [];

    public $attachOne = [];

    public $attachMany = [];

    public function scopeName($q, string $name)
    {
        return $q->where('name', '=', $name);
    }

    public function scopeBooksEager(Builder $builder): Builder
    {
        return $builder->with(['books' => fn($books) => $books->public()->defaultEager()]);
    }

    public function getLastUpdatedAtAttribute()
    {
        return $this->books->map->ebook->pluck('last_updated_at')->sortDesc()?->first();
    }
}
