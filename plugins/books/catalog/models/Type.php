<?php

namespace Books\Catalog\Models;

use Books\Book\Classes\Enums\EditionsEnums;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;

/**
 * Type Model
 *
 * @property string label
 * @property EditionsEnums type
 */
class Type extends Model
{
    use Validation;
    use Sortable;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_catalog_types';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'type',
        'sort_order',
        'slug',
        'desc',
        'h1',
        'meta_title',
        'meta_desc',
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'type' => 'required|integer',
        'sort_order' => 'required|integer',
        'slug' => 'string|nullable|regex:/^[a-z]+(?:-[a-z]+)*$/',
        'desc' => 'string|nullable',
        'h1' => 'string|nullable',
        'meta_title' => 'string|nullable',
        'meta_desc' => 'string|nullable',
    ];

    public $customMessages = [
        'slug.regex' => 'Неправильный формат строки для поля `Страница`. Используйте латинские символы (abcdefghijklmnopqrstuvwxyz) и разделитель (`-`)',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'type' => EditionsEnums::class,
    ];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

    /**
     * @var array appends attributes to the API representation of the model (ex. toArray())
     */
    protected $appends = [

    ];

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

    public $belongsToMany = [];

    public $morphTo = [];

    public $morphOne = [];

    public $morphMany = [];

    public $attachOne = [
        'icon' => 'System\Models\File',
    ];

    public $attachMany = [];

    public function getLabelAttribute(): ?string
    {
        return $this->type?->label();
    }

    public function activate()
    {
        $this->active = 1;
        $this->save();

        return $this;
    }

    public function deactivate()
    {
        $this->active = 0;
        $this->save();

        return $this;
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
