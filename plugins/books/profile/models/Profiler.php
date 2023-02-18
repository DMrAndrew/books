<?php

namespace Books\Profile\Models;

use October\Rain\Database\Builder;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;

/**
 * Profiler Model
 */
class Profiler extends Model
{
    use Validation;

    public $timestamps = false;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_profile_profilers';

    const IDS_FIELD = 'slave_ids';


    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = ['slave_type', 'slave_ids'];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [
        'slave_ids',
    ];

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

    public $belongsToMany = [];

    public $morphTo = [
        'master' => []
    ];

    public $morphOne = [];

    public $morphMany = [];

    public $attachOne = [];

    public $attachMany = [];

    public function getIds()
    {
        return $this->{self::IDS_FIELD};
    }

    public function getIdsColumn(): string
    {
        return self::IDS_FIELD;
    }

    public function scopeSlaveType(Builder $builder, Model $model): Builder
    {
        return $builder->where('slave_type', '=', get_class($model));
    }

    public function scopeMasterType(Builder $builder, Model $model): Builder
    {
        return $builder->where('master_type', '=', get_class($model));
    }

}
