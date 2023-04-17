<?php

namespace Books\Book\Behaviors;

use Books\Book\Models\Edition;
use Books\Book\Models\Tracker;
use Books\Collections\classes\CollectionEnum;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class Trackable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->morphMany['trackers'] = [Tracker::class, 'name' => 'trackable'];
    }

    public function trackTime(User $user, $time = 0, $unit = 'ms')
    {
        if (! $time) {
            return null;
        }
        $time = (int) floor(match ($unit) {
            'ms', 'millisecond' => $time / 1000,
            's', 'sec', 'seconds' => $time,
            'm', 'min', 'minutes' => $time * 60
        });
        $tracker = $this->model->trackByUser($user);
        $tracker?->update(['time' => $tracker->time + $time]);
        Event::fire('books.paginator.tracked', [$tracker]);

        return $tracker;
    }

    public function trackByUser(User $user): Tracker
    {
        return $this->model->trackers()->firstOrCreate(['user_id' => $user->id]);
    }

    public function scopeWithReadTrackersCount(Builder $builder): Builder
    {
        return $builder->withCount(['trackers as completed_trackers' => fn ($trackers) => $trackers->withoutTodayScope()->completed()]);
    }

    public function scopeCountUserTrackers(Builder $builder, User $user): Builder
    {
        return $builder->withCount(['trackers' => fn ($i) => $i->user($user)]);
    }

    public function computeProgress(?User $user = null)
    {
        if (! $this->model->trackerChildRelation || ! $this->model->hasRelation($this->model->trackerChildRelation)) {
            return false;
        }

        return $this->model
            ->{$this->model->trackerChildRelation}()
            ->with('trackers')->get()
            ->pluck('trackers')
            ->flatten(1)
            ->filter(fn ($i) => ! $user || $i['user_id'] === $user->id)
            ->groupBy('user_id')
            ->map(function ($trackers, $user_id) {
                $user = User::find($user_id);
                $tracker = $this->model->trackByUser($user);

                $progress = (int) ceil(
                    $trackers->pluck('progress') //прогресс по всем трекнутым
                    ->pad($this->model->{$this->model->trackerChildRelation}()->count(), 0)// добиваем до общего кол-ва
                    ->avg() // profit
                );

                $tracker->update([
                    'length' => $trackers->sum('length'),
                    'time' => $trackers->sum('time'),
                    'progress' => $progress,
                ]);

                //когда пользователь открыл книгу и начал читать (от 3-х страниц) добавляем в раздел "читаю сейчас" если книга в библиотеке и в разделе "Хочу прочесть"
                if ($this->model instanceof Edition) {
                    $lib = $user->library($this->model->book);
                    if ($lib->is(CollectionEnum::INTERESTED)) {
                        //TODO ref
                        $trackers_count = $this->model
                            ->chapters()
                            ->select('id')
                            ->with(['pagination' => fn ($p) => $p->select(['id', 'chapter_id'])->countUserTrackers($user)])
                            ->get()->pluck('pagination')
                            ->flatten(1)
                            ->pluck('trackers_count')->sum();
                        if ($trackers_count > 3) {
                            $lib->reading();
                        }
                    }
                }

                return $progress;
            });
    }
}
