<?php

namespace Books\Book\Models;

use Books\Book\Classes\ScopeToday;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * Tracker Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Tracker extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_trackers';

    protected $fillable = ['time', 'progress', 'length', 'user_id', 'data'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'user_id' => 'required|exists:users,id',
        'time' => 'integer',
        'length' => 'integer',
        'progress' => 'integer|between:0,100',
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
        'user' => [User::class, 'key' => 'id', 'otherKey' => 'user_id'],
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
        return $builder->orderBy('updated_at', $asc ? 'asc' : 'desc');
    }

    public function scopeCompleted(Builder $builder): Builder
    {
        return $builder->minProgress(100);
    }

    public function scopeMinProgress(Builder $builder, int $progress): Builder
    {
        return $builder->where('progress', '>=', $progress);
    }

    public function scopeMaxProgress(Builder $builder, int $progress): Builder
    {
        return $builder->where('progress', '<=', $progress);
    }

    public function scopeType(Builder $builder, string $class): Builder
    {
        return $builder->where('trackable_type', '=', $class);
    }

    public function scopeUser(Builder $builder, ?User $user): Builder
    {
        return $builder->where('user_id', '=', $user?->id);
    }
}
