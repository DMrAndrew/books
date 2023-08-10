<?php

namespace Books\Book\Behaviors;

use Books\Book\Models\Edition;
use Books\Book\Models\Tracker;
use Books\Collections\classes\CollectionEnum;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

class Trackable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->morphMany['trackers'] = [Tracker::class, 'name' => 'trackable'];
    }


    public function getTracker(?User $user = null, ?string $ip = null)
    {
        return $this->model->trackers()->userOrIpWithDefault($user, $ip)->first()
            ??
            $this->model->trackers()->create([
                'user_id' => ($user ?? Auth::getUser())?->id,
                'ip' => $ip ?? request()->ip()
            ]);
    }

    public function scopeWithReadTrackersCount(Builder $builder, ?int $ofLastDays = null): Builder
    {
        return $builder->withCount(['trackers as completed_trackers' => fn($trackers) => $trackers->withoutTodayScope()->completed()->when($ofLastDays, fn($b) => $b->ofLastDays($ofLastDays))]);
    }

    public function scopeCountUserTrackers(Builder $builder, User $user): Builder
    {
        return $builder->withCount(['trackers' => fn($i) => $i->user($user)]);
    }

    public function computeProgress()
    {
        return false;

        if (!$this->model->trackerChildRelation || !$this->model->hasRelation($this->model->trackerChildRelation)) {
            return false;
        }


        $trackers = $this->model->{$this->model->trackerChildRelation}()
            ->with(['trackers'])
            ->get()
            ->pluck('trackers')
            ->flatten(1);


        $byUser = $trackers->where('user_id', '!=', null)->groupBy('user_id')->map(function ($group, $id) {
            $user = User::find($id);
            if ($user) {
                $tracker = $this->model->getTracker(user: $user);
                $this->collectProgress($tracker, $group);
                if ($user && $this->model instanceof Edition) {
                    $this->toLib($user);
                }
            }
            return $group;

        });
        $byIP = $trackers->where('user_id', '=', null)->groupBy('ip')->map(function ($group, $ip) {
            $tracker = $this->model->getTracker(user: null, ip: $ip);
            $this->collectProgress($tracker, $group);
            return $group;
        });

        return $byUser->concat($byIP);


    }

    public function collectProgress($tracker, $array)
    {
        $progress = (int)ceil($array
            ->pluck('progress') //прогресс по всем трекнутым
            ->pad($this->model->{$this->model->trackerChildRelation}()->count(), 0)// добиваем до общего кол-ва
            ->avg() // profit
        );

        $tracker->update([
            'length' => $array->sum('length'),
            'time' => $array->sum('time'),
            'progress' => $progress,
        ]);

        return $progress;

    }


    /**
     * Когда пользователь открыл книгу и начал читать (от 3-х страниц)
     * добавляем в раздел "читаю сейчас"
     * если книга в библиотеке и в разделе "Хочу прочесть"
     * @param $user
     * @return void
     */
    public function toLib($user)
    {
        $lib = $user->library($this->model->book);
        if ($lib->is(CollectionEnum::INTERESTED)) {
            //TODO ref
            $trackers_count = $this->model
                ->chapters()
                ->select('id')
                ->with(['pagination' => fn($p) => $p->select(['id', 'chapter_id'])->countUserTrackers($user)])
                ->get()->pluck('pagination')
                ->flatten(1)
                ->pluck('trackers_count')->sum();
            if ($trackers_count > 3) {
                $lib->reading();
            }
        }

    }
}
