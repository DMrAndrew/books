<?php

namespace Books\Book\Models;

use Illuminate\Support\Collection;
use Model;
use October\Rain\Database\Traits\Revisionable;
use October\Rain\Database\Traits\Validation;
use System\Models\Revision;

/**
 * Stats Model
 *
 * @property int likes
 * @property int rate
 * @property int comments
 * @property int freq
 * @property int libs
 * @property int read_time
 * @property int read_count
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Stats extends Model
{
    use Validation;
    use Revisionable;

    /**
     * @var string table name
     */
    public $table = 'books_book_stats';

    protected $revisionable = ['rate'];

    protected $fillable = [
        'book_id',
        'likes_count',
        'in_lib_count',
        'read_count',
        'rate',
        'comments_count',
        'freq',
        'read_time', 'history'];

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

    public $morphMany = [
        'revision_history' => [Revision::class, 'name' => 'revisionable'],
    ];

    public function getLikesAttribute()
    {
        return $this->likes_count;
    }

    public function getLibsAttribute()
    {
        return $this->in_lib_count;
    }

    public function getCommentsAttribute()
    {
        return $this->comments_count;
    }

    public function dump()
    {
        $h = $this->history;
        $attributes = $this->attributes;
        unset($attributes['history']);
        $h[$this->updated_at->format('d.m.y')] = $attributes;
        $this->history = $h;
        $this->save();
    }

    public function forGenres(bool $includeFreq = true): float|int
    {
        return $this->toRateValue(collect([
            $this->rate,
            $this->comments,
            $this->libs,
            $this->likes,
            $this->read_time,
            $this->read_count,
            $includeFreq ? $this->freq : 0,
        ]));
    }

    public function gainingPopularity(bool $includeFreq = true): float|int
    {
        return $this->toRateValue(collect([
            $this->rate,
            $this->comments * 2,
            $this->libs * 2,
            $this->likes,
            $this->read_time * 3,
            $this->read_count,
            $includeFreq ? $this->freq : 0,
        ]));
    }

    public function popular(): float|int
    {
        return $this->toRateValue(collect([
            $this->rate,
            $this->read_time * 3,
            $this->comments * 2,
            $this->read_count,
        ]));
    }

    public function hotNew(bool $includeFreq = true): float|int
    {
        return $this->toRateValue(collect([
            $this->rate,
            $this->comments,
            $this->libs,
            $this->likes,
            $this->read_time * 3,
            $this->read_count,
            $includeFreq ? $this->freq * 3 : 0,
        ]));
    }

    public function toRateValue(Collection $collection): float|int
    {
        $collection = $collection->filter(fn ($i) => (bool) $i);

        return $collection->count() ? $collection->sum() / $collection->count() : 0;
    }
}
