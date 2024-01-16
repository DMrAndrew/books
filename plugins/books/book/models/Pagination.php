<?php

namespace Books\Book\Models;

use Event;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Database\Traits\Purgeable;
use October\Rain\Database\Traits\Validation;

/**
 * Pagination Model
 *
 * @method HasOne chapter
 *
 * @property  ?Chapter chapter
 * @property ?Pagination next
 * @property ?Pagination prev
 *
 * @method HasOne next
 * @method HasOne prev
 * @method HasMany trackers
 */
class Pagination extends Model
{
    use Validation;
    use Purgeable;

    protected $purgeable = ['new_content'];

    /**
     * @var string table name
     */
    public $table = 'books_book_pagination';

    public const RECOMMEND_MAX_LENGTH = 7500;

    protected $fillable = [
        'page',
        'length',
        'new_content',
        'chapter_id',
        'next_id',
        'prev_id',
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'page' => 'required|integer',
        'length' => 'required|integer',
        'chapter_id' => 'required|integer|exists:books_book_chapters,id',
        'next_id' => 'nullable|integer|exists:books_book_pagination,id',
        'prev_id' => 'nullable|integer|exists:books_book_pagination,id',
    ];

    public $belongsTo = [
        'chapter' => [Chapter::class, 'key' => 'chapter_id', 'otherKey' => 'id'],
        'next' => [Pagination::class, 'key' => 'next_id', 'otherKey' => 'id'],
        'prev' => [Pagination::class, 'key' => 'prev_id', 'otherKey' => 'id'],
    ];

    public function scopePage(Builder $builder, int $page): Builder
    {
        return $builder->where('page', '=', $page);
    }

    public function setNeighbours(): void
    {
        $builder = fn ($page) => $this->chapter->pagination()->page($page)->value('id');
        $this->update([
            'next_id' => $builder($this->page + 1),
            'prev_id' => $builder($this->page - 1),
        ]);
    }

    /**
     * Отслеживает время, потраченное на чтение.
     *
     * @param  int  $time Время, потраченное на чтение, в указанной единице измерения.
     * @param  string  $unit Единица измерения времени (ms, s, m), в которой указано время. По умолчанию - 'ms'.
     * @return ?Tracker Возвращает объект трекера.
     */
    public function trackTime(int $time = 0, string $unit = 'ms'): ?Tracker
    {

        if (! $time) {
            return null;
        }
        $time = (int) floor(num: match ($unit) {
            'ms', 'millisecond' => $time / 1000,
            's', 'sec', 'seconds' => $time,
            'm', 'min', 'minutes' => $time * 60
        });

        $tracker = $this->getTracker();
        $tracker?->increment('time', $time);
        Event::fire('books.paginator.tracked', ['tracker_id' => $tracker->id]);

        return $tracker;
    }

    public function scopeHasDuplicates(Builder $builder)
    {
        $tracker = Tracker::make();
        $columns = ['user_id', 'ip'];
        foreach ($columns as $groupBy) {
            $column = $tracker->qualifyColumn($groupBy);
            $raw = sprintf('DATE(%s) as date ,%s, count(*) as total_trackers',
                $tracker->qualifyColumn('created_at'),
                $column,
            );
            $sub_query = fn ($q) => $q->withoutTodayScope()->selectRaw($raw)->groupBy('date', $column)->having('total_trackers', '>', 1);
            $builder->orWhereHas('trackers', $sub_query);
        }

        return $builder;
    }
}
