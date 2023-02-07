<?php namespace Books\User\Models;

use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * Country Model
 */
class Country extends Model
{
    use Validation;
    use Sortable;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_user_countries';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = ['name', 'code', 'sort_order'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'name' => 'required|string',
        'code' => 'required|string:max:3|unique:books_user_countries',
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
    public $belongsToMany = ['users' => [User::class, 'key' => 'country_id', 'otherKey' => 'id']];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function scopeCode($q, string ...$code)
    {
        return $q->whereIn('code', $code);
    }
}
