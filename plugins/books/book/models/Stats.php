<?php

namespace Books\Book\Models;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Traits\Validation;

//use October\Rain\Database\Traits\Revisionable;
//use System\Models\Revision;

/**
 * Stats Model
 *
 * @property int likes_count
 * @property int rate
 * @property int comments_count
 * @property int freq
 * @property int in_lib_count
 * @property int read_time
 * @property int read_count
 * @property int collected_genre_rate
 * @property int collected_gain_popularity_rate
 * @property int collected_hot_new_rate
 * @property int sells_count
 * @property int read_initial_count
 * @property int read_final_count
 *
 * @method BelongsTo book
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Stats extends Model
{
    use Validation;

//    use Revisionable;

    /**
     * @var string table name
     */
    public $table = 'books_book_stats';

    const HISTORY_EXPIRATION_DAYS = 3;

//    protected $revisionable = ['rate'];

    protected $fillable = [
        'book_id',
        'likes_count', //Лк
        'in_lib_count', //Кбд
        'read_count',//Кпг
        'rate', // Рк
        'comments_count', // Kк
        'freq', //Чо
        'read_time', //Чп
        'collected_gain_popularity_rate',
        'collected_hot_new_rate',
        'collected_genre_rate',
        'collected_popular_rate',
        'sells_count', //П
        'read_final_count', //Кпг1
        'read_initial_count', //Кппг
        'history'];

    protected $casts = [
        'likes_count' => 'integer',
        'in_lib_count' => 'integer',
        'read_count' => 'integer',
        'rate' => 'integer',
        'comments_count' => 'integer',
        'freq' => 'integer',
        'read_time' => 'integer',
        'collected_gain_popularity_rate' => 'integer',
        'collected_hot_new_rate' => 'integer',
        'collected_genre_rate' => 'integer',
        'collected_popular_rate' => 'integer',
        'sells_count' => 'integer',
    ];
    /**
     * @var array rules for validation
     */
    public $rules = [
        'likes_count' => 'nullable|integer|min:0',
        'in_lib_count' => 'nullable|integer|min:0',
        'read_count' => 'nullable|integer|min:0',
        'rate' => 'nullable|integer|min:0',
        'comments_count' => 'nullable|integer|min:0',
    ];

    public $belongsTo = [
        'book' => [Book::class, 'key' => 'id', 'otherKey' => 'book_id'],
    ];

    protected $jsonable = ['history'];

//    public $morphMany = [
//        'revision_history' => [Revision::class, 'name' => 'revisionable'],
//    ];


    public function dump(): static
    {
        $h = collect($this->history);
        $attributes = $this->attributes;
        unset($attributes['history']);
        $h[Carbon::now()->format('d.m.y')] = $attributes;
        if ($h->keys()->count() >= self::HISTORY_EXPIRATION_DAYS) {
            $h->shift();
        }
        $this->history = $h->toArray();
        return $this;
    }

    public function scopeValidParamValue(Builder $builder, string $param): Builder
    {
        return $builder->whereNotNull($param)->where($param, '>', 0);
    }

    public function clearDumps(): static
    {
        $this->history = [];
        return $this;
    }

    public function forGenres(bool $includeFreq = true): float|int
    {
        return $this->toRateValue([
            $this->rate,
            $this->comments_count,
            $this->in_lib_count,
            $this->likes_count,
            $this->read_time,
            $this->read_count,
            $includeFreq ? $this->freq : null,
        ]);
    }

    public function gainingPopularity(bool $includeFreq = true): float|int
    {
        return $this->toRateValue([
            $this->rate,
            $this->comments_count * 2,
            $this->in_lib_count * 2,
            $this->likes_count,
            $this->read_time * 3,
            $this->read_count,
            $includeFreq ? $this->freq : null,
        ]);
    }

    public function hotNew(bool $includeFreq = true): float|int
    {
        return $this->toRateValue([
            $this->rate,
            $this->comments_count,
            $this->in_lib_count,
            $this->likes_count,
            $this->read_time * 3,
            $this->read_count,
            $includeFreq ? $this->freq * 3 : null,
        ]);
    }

    public function popular(): float|int
    {
        return $this->toRateValue([
            $this->rate,
            $this->read_time * 3,
            $this->comments_count * 2,
            $this->read_count,
            $this->sells_count ?: null
        ]);
    }

    public function toRateValue(array|Collection $collection): int
    {
        $collection = collect($collection)->filter(fn($i) => !is_null($i));

        return (int)($collection->count() ? ($collection->sum() / $collection->count()) : 0);
    }
}
