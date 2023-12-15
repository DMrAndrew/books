<?php

namespace Books\Catalog\Models;

use Books\Book\Models\Book;
use Books\Book\Models\BookGenre;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Traits\NestedTree;
use October\Rain\Database\Traits\Nullable;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\TreeCollection;

/**
 * Genre Model
 *
 * @method HasMany children
 *
 * @property  TreeCollection children
 * @property  string slug
 */
class Genre extends Model
{
    use Validation;
    use NestedTree;
    use Nullable;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_catalog_genres';

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'name',
        'slug',
        'desc',
        'h1',
        'meta_title',
        'meta_desc',
        'active',
        'favorite',
        'parent_id',
        'adult',
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'name' => 'required|string|min:3',
        'slug' => 'sometimes|string|nullable|regex:/^[a-z]+(?:-[a-z]+)*$/',
        'desc' => 'sometimes|string|nullable',
        'h1' => 'sometimes|string|nullable',
        'meta_title' => 'sometimes|string|nullable',
        'meta_desc' => 'sometimes|string|nullable',
        'active' => 'boolean',
        'favorite' => 'boolean',
        'parent_id' => 'nullable|exists:books_catalog_genres,id',
    ];

    public $customMessages = [
        'slug.regex' => 'Неправильный формат строки для поля `Страница`. Используйте латинские символы (abcdefghijklmnopqrstuvwxyz) и разделитель (`-`)',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    public $nullable = [
        'slug',
        'desc',
        'h1',
        'meta_title',
        'meta_desc',
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
            'pivotModel' => BookGenre::class,
            'key' => 'genre_id',
            'otherKey' => 'book_id',
            'pivot' => ['rate_number'],
        ],
    ];

    public $morphTo = [];

    public $morphOne = [];

    public $morphMany = [];

    public $attachOne = [];

    public $attachMany = [];

    public function checkAdult(): static
    {
        $this->update(['adult' => 1]);

        return $this;
    }

    public function uncheckAdult(): static
    {
        $this->update(['adult' => 0]);

        return $this;
    }

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

    public function scopeOrFavorite(Builder $builder): Builder
    {
        return $builder->orWhere('favorite', '=', 1);
    }

    public function scopeActive(Builder $builder): Builder
    {
        return $builder->where('active', '=', 1);
    }

    public function scopeName(Builder $builder, string $name): Builder
    {
        return $builder->where('name', 'like', "%$name%");
    }

    public function scopeNameLike(Builder $builder, string $name)
    {
        return $builder->name($name);
    }

    public function scopeAsOption(Builder $builder): Builder
    {
        return $builder->select(['id', 'name']);
    }

    public function scopeAdult(Builder $builder, bool $value = true): Builder
    {
        return $builder->where('adult', '=', $value);
    }

    public function scopePublic(Builder $builder): Builder
    {
        if (shouldRestrictAdult()) {
            $builder->adult(false);
        }

        return $builder->withoutProhibited();
    }

    public function scopeNestedFavorites(Builder $builder): Builder
    {
        return $builder
            ->where(fn($q) => $q->roots()->whereHas('children', fn($q) => $q->favorite()))
            ->orWhere(fn($q) => $q->roots()->favorite())
            ->with('children', fn($q) => $q->favorite());
    }

    /**
     * @param Builder $builder
     * @param string $slug
     *
     * @return Builder
     */
    public function scopeSlug(Builder $builder, string $slug): Builder
    {
        return $builder->where('slug', $slug);
    }
}
