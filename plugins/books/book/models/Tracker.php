<?php

namespace Books\Book\Models;

use App\traits\HasUserIPScopes;
use Books\Book\Classes\ScopeToday;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\MorphTo;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * Tracker Model
 *
 * @property int $time time in sec
 * @property  Model trackable
 * @property  User user
 * @method MorphTo trackable
 * @method BelongsTo user
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Tracker extends Model
{
    use Validation;
    use HasUserIPScopes;

    /**
     * @var string table name
     */
    public $table = 'books_book_trackers';

    protected $fillable = ['time', 'progress', 'length', 'user_id', 'data', 'ip'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'user_id' => 'nullable|exists:users,id',
        'time' => 'integer',
        'length' => 'integer',
        'progress' => 'integer|between:0,100',
        'ip' => 'required|ip'
    ];

    protected $casts = [
        'time' => 'integer',
        'length' => 'integer',
        'progress' => 'integer',
    ];

    protected $jsonable = [
        'data',
    ];

    public $belongsTo = [
        'user' => [User::class, 'key' => 'user_id', 'otherKey' => 'id'],
    ];

    public $morphTo = [
        'trackable' => [],
    ];

    protected static function booted()
    {
        static::addGlobalScope(new ScopeToday());
    }


    public function scopeOrderByUpdatedAt(Builder $builder, bool $asc = true): Builder
    {
        return $builder->orderBy($this->getQualifiedUpdatedAtColumn(), $asc ? 'asc' : 'desc');
    }

    public function scopeCompleted(Builder $builder): Builder
    {
        return $builder->minProgress(100);
    }

    public function scopeMinProgress(Builder $builder, int $progress): Builder
    {
        return $builder->where('progress', '>=', $progress);
    }

    public function scopeMaxTime(Builder $builder, int $value): Builder
    {
        return $builder->where('time', '<=', $value);
    }

    public function scopeMinTime(Builder $builder, int $value): Builder
    {
        return $builder->where('time', '>=', $value);
    }

    public function scopeMaxProgress(Builder $builder, int $progress): Builder
    {
        return $builder->where('progress', '<=', $progress);
    }

    public function scopeType(Builder $builder, string $class): Builder
    {
        return $builder->where('trackable_type', '=', $class);
    }

}
