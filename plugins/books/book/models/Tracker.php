<?php

namespace Books\Book\Models;

use App\traits\HasUserIPScopes;
use Books\Book\Classes\ReadProgress;
use Books\Book\Classes\ScopeToday;
use Books\Book\Jobs\ClearTrackers;
use Books\Book\Jobs\Reading;
use Carbon\Carbon;
use Db;
use Illuminate\Notifications\Notifiable;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Relations\MorphTo;
use October\Rain\Database\Traits\SimpleTree;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * Tracker Model
 *
 * @property int $time time in sec
 * @property  Model trackable
 * @property  User user
 *
 * @method MorphTo trackable
 * @method BelongsTo user
 * @method BelongsTo parent
 * @method HasMany children
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Tracker extends Model
{
    use Notifiable;
    use Validation;
    use HasUserIPScopes;
    use SimpleTree;

    /**
     * @var string table name
     */
    public $table = 'books_book_trackers';

    protected $fillable = ['time', 'progress', 'length', 'user_id', 'data', 'ip', 'parent_id'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'user_id' => 'nullable|exists:users,id',
        'time' => 'integer',
        'length' => 'integer',
        'progress' => 'integer|between:0,100',
        'ip' => 'required|ip',
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

    protected static function booted(): void
    {
        static::addGlobalScope(new ScopeToday());
    }

    public function scopeUnParent(Builder $builder)
    {
        return $builder->whereNull('parent_id');
    }

    public function scopeBroken(Builder $builder)
    {
//        return $builder->whereDate('created_at', '<=', Carbon::parse('2023-09-15'));
        $builder->whereDate('created_at', '>=', Carbon::parse('2023-09-01'))
        ->whereDate('created_at', '<=', Carbon::parse('2023-09-15'));
    }

    public function scopeOrderByUpdatedAt(Builder $builder, bool $asc = true): Builder
    {
        return $builder->orderBy($this->getQualifiedUpdatedAtColumn(), $asc ? 'asc' : 'desc');
    }

    public function scopeCompleted(Builder $builder): Builder
    {
        return $builder->minProgress(100);
    }

    public function scopeMaxTime(Builder $builder, int $value): Builder
    {
        return $builder->where($this->getQualifiedTimeColumn(), '<=', $value);
    }

    public function scopeMinTime(Builder $builder, int $value): Builder
    {
        return $builder->where($this->getQualifiedTimeColumn(), '>=', $value);
    }

    public function scopeMinProgress(Builder $builder, int $progress): Builder
    {
        return $builder->where($this->getQualifiedProgressColumn(), '>=', $progress);
    }

    public function scopeMaxProgress(Builder $builder, int $progress): Builder
    {
        return $builder->where($this->getQualifiedProgressColumn(), '<=', $progress);
    }

    public function scopeType(Builder $builder, string $class): Builder
    {
        return $builder->where($this->qualifyColumn('trackable_type'), '=', $class);
    }

    public function scopeLatestActiveTracker(Builder $builder)
    {
        return $builder
            ->withoutTodayScope()
            ->userOrIpWithDefault()
            ->whereHasMorph(
                'trackable',
                Pagination::class,
                fn ($i) => $i->whereHas('chapter', fn ($chapter) => $chapter->whereNull(Chapter::make()->getQualifiedDeletedAtColumn())))
            ->orderByUpdatedAt(asc: false);
    }

    public function getQualifiedProgressColumn(): string
    {
        return $this->qualifyColumn('progress');
    }

    public function getQualifiedTimeColumn(): string
    {
        return $this->qualifyColumn('time');
    }

    public function getQualifiedLengthColumn(): string
    {
        return $this->qualifyColumn('length');
    }

    public function clearTrackers(): void
    {
        ClearTrackers::dispatch();
    }

    public function clearDuplicates(): int
    {
        $duplicates = $this->trackable?->trackers()
            ->withoutTodayScope()
            ->userOrIp($this->user, $this->ip)
            ->whereDate('created_at', $this->created_at)
            ->get() ?? collect();
        if ($duplicates->count() > 1) {
            $init = $duplicates->sortBy('created_at')->first();
            $init->fill([
                'length' => $duplicates->max('length'),
                'progress' => $duplicates->max('progress'),
                'time' => $duplicates->sum('time'),
                'updated_at' => $duplicates->sortByDesc('updated_at')->first()->updated_at,
                'ip' => $this->ip ?? request()->ip(),
            ]);

            return Db::transaction(function () use ($init, $duplicates) {
                $init->timestamps = false;
                $init->save(['force' => true]);

                return static::query()->withoutTodayScope()
                    ->whereIn('id', $duplicates->where('id', '!=', $init->id)
                        ->pluck('id')
                        ->toArray())
                    ->delete();
            });

        }

        return 0;

    }

    public function progress(): void
    {
        Reading::dispatch($this);
    }

    public function readProgressService(): ReadProgress
    {
        return new ReadProgress($this);
    }

    public function afterTrack(): void
    {
        $this->readProgressService()->apply();
    }
}
