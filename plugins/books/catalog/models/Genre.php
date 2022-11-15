<?php namespace Books\Catalog\Models;

use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\NestedTree;
use October\Rain\Database\Traits\Validation;

/**
 * Genre Model
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
    protected $fillable = ['name', 'desc', 'active', 'favorite','parent_id'];

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
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

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

    public function enableFavorite()
    {
        $this->favorite = 1;
        $this->save();

        return $this;
    }

    public function disableFavorite()
    {
        $this->favorite = 0;
        $this->save();

        return $this;

    }

    /**
     * getUserOptions
     */
    public function getParentOptions()
    {
        $options = [];

        foreach (static::all() as $genre) {
            $options[$genre->id] = $genre->name;
        }

        return $options;
    }


    public function scopeParent(Builder $query){
        return $query->whereNull('parent_id');
    }
    public function scopeFavorites(Builder $query){
        return $query->where('favorite','=',1);
    }

    public function scopeActive(Builder $query){
        return $query->where('active','=',1);
    }
}
