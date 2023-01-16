<?php namespace Books\Catalog\Models;


use Db;
use Model;
use Books\Book\Models\Book;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Traits\NestedTree;
use October\Rain\Database\Traits\Validation;

/**
 * Genre Model
 *
 * @method HasMany children
 */
class Genre extends Model
{
    use Validation;
    use NestedTree;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_catalog_genres';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = ['name', 'desc', 'active', 'favorite', 'parent_id'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'name' => 'required|string|min:3',
        'desc' => 'string|nullable',
        'active' => 'boolean',
        'favorite' => 'boolean',
        'parent_id' => 'nullable|exists:books_catalog_genres,id'
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
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [
        'books' => [
            Book::class,
            'table' => 'books_book_genre',
            'key' => 'genre_id',
            'otherKey' => 'book_id'
        ]
    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function activate(): static
    {
        $this->update(['active' => 1]);

        return $this;
    }

    public function deactivate(): static
    {
        $this->update(['active' => 0]);

        return $this;
    }

    public function enableFavorite(): static
    {
        $this->update(['favorite' => 1]);

        return $this;
    }

    public function disableFavorite(): static
    {
        $this->update(['favorite' => 0]);

        return $this;

    }

    /**
     * getParentOptions
     */
    public function getParentOptions(): array
    {
        return static::lists('name', 'id');
    }


    public function scopeRoots(Builder $builder): Builder
    {
        return $builder->whereNull('parent_id');
    }

    public function scopeChild(Builder $builder): Builder
    {
        return $builder->whereNotNull('parent_id');
    }

    public function scopeFavorite(Builder $builder): Builder
    {
        return $builder->where('favorite', '=', 1);
    }

    public function scopeActive(Builder $builder): Builder
    {
        return $builder->where('active', '=', 1);
    }

    public function scopeName(Builder $builder, string $name): Builder
    {
        return $builder->where('name', 'like', "%$name%");
    }
}
