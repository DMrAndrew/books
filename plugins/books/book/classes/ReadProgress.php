<?php

namespace Books\Book\Classes;

use Books\Book\Models\Chapter;
use Books\Book\Models\Pagination;
use Books\Book\Models\Tracker;
use Db;
use October\Rain\Database\Collection;

class ReadProgress
{
    protected ?string $parent_relation = null;

    protected Tracker $parent_tracker;

    protected string $base_column = 'user_id';

    protected int|string $value;

    public function __construct(public Tracker $tracker)
    {
        $this->tracker->timestamps = false;

        $this->parent_relation = $this->getParentRelation();

        $this->base_column = $this->tracker->qualifyColumn($this->tracker->user_id ? 'user_id' : 'ip');

        $this->value = $this->tracker->user_id ?? $this->tracker->ip;

    }

    public function validate(): bool
    {
        return in_array($this->parent_relation, ['chapter', 'edition']);
    }

    public function apply(): ?int
    {
        if (! $this->validate()) {
            return null;
        }

        return Db::transaction(function () {
            if (! $this->parentTrackerBuilder()->exists()) {
                $this->createParentTracker();
            }

            $this->parent_tracker = $this->parentTrackerBuilder()->first();

            $this->attachParent();

            return $this->compute();

        });
    }

    public function attachParent(): void
    {
        $this->tracker->fill([
            'parent_id' => $this->parent_tracker->id,
            'ip' => $this->tracker->ip ? $this->tracker->ip : request()->ip()]);
        $this->tracker->save(['timestamps' => false, 'force' => true]);
    }

    public function getParentRelation(): ?string
    {
        return match (get_class($this->tracker->trackable)) {
            Pagination::class => 'chapter',
            Chapter::class => 'edition',
            default => null
        };
    }

    public function getTotalItems(): int
    {
        return (match (get_class($this->tracker->trackable)) {
            Pagination::class => $this->tracker->trackable->chapter->pagination(),
            Chapter::class => $this->tracker->trackable->edition->chapters()->public(),
        })->count();
    }

    public function parentTrackerBuilder()
    {
        return $this->tracker->trackable->{$this->parent_relation}
            ->trackers()
            ->withoutTodayScope()
            ->when(is_null($this->tracker->user_id), fn ($q) => $q->whereNull('user_id'))
            ->where(fn ($q) => $q->where(fn ($b) => $b->where('progress', '<', 100)->whereDate('created_at', '<=', $this->tracker->created_at))
                ->orWhere(fn ($b) => $b->whereDate('created_at', '<=', $this->tracker->created_at)))
            ->whereNotNull($this->base_column)
            ->where($this->base_column, $this->value);
    }

    public function createParentTracker(): void
    {
        $new = $this->parentTrackerBuilder()->create([
            'user_id' => $this->tracker->user_id,
            'ip' => $this->tracker->ip ? $this->tracker->ip : request()->ip(),
            'created_at' => $this->tracker->created_at,
        ]);
        $new->created_at = $new->updated_at = $this->tracker->created_at;
        $new->save(['force' => true, 'timestamps' => false]);
    }

    public function compute(): int
    {
        $children = $this->parent_tracker->children()->withoutTodayScope()->get();
        $progress = $this->progress($children->unique('trackable_id'));
        $this->parent_tracker->fill([
            'length' => $children->unique('trackable_id')->sum('length'),
            'time' => $children->sum('time'),
            'progress' => $progress,
        ]);
        $this->parent_tracker->updated_at = $this->tracker->updated_at;
        $this->parent_tracker->save(['timestamps' => false, 'force' => true]);
        $this->parent_tracker->afterTrack();

        return $progress;
    }

    public function progress(Collection $trackers): int
    {
        /**
         * Для первой и последней частей сразу засчитываем прочтение всей части при переходе на 1-ю страницу
         */
        if ($this->parent_relation === 'chapter'
            && $this->tracker->trackable->page == 1
            && ($this->tracker->trackable->chapter->prev()->public()->doesntExist()
                || $this->tracker->trackable->chapter->next()->public()->doesntExist())) {
            return 100;
        }

        return (int) ceil($trackers->pluck('progress')
            ->pad($this->getTotalItems(), 0)
            ->avg());
    }
}
