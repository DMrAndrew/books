<?php

namespace Books\Book\Models;

use Books\Book\Classes\Enums\BookStatus;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

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
        'user_id' => 'required|exists:users,id',
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
        return $builder->with(['books' => fn ($books) => $books->defaultEager()]);
    }

    public function getStatusAttribute(): BookStatus
    {
        return $this->books->pluck('status')->some(fn ($i) => $i === BookStatus::WORKING) ? BookStatus::WORKING : BookStatus::COMPLETE;
    }

    public function getLastUpdatedAtAttribute()
    {
        return $this->books->map->ebook->pluck('last_updated_at')->sortDesc()?->first();
    }
}
