<?php

namespace Books\Book\Behaviors;

use Books\Book\Models\Edition;
use Books\Book\Models\Tracker;
use Books\Collections\classes\CollectionEnum;
use Log;
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
        return $this->model
            ->trackers()
            ->userOrIP($user, $ip)
            ->first()
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

    public function computeProgress(?User $user = null)
    {
        if (!$this->model->trackerChildRelation || !$this->model->hasRelation($this->model->trackerChildRelation)) {
            return false;
        }


        $trackers = $this->model->{$this->model->trackerChildRelation}()
            ->with(['trackers' => fn($trackers) => $trackers->userOrIp($user)])
            ->get()
            ->pluck('trackers')
            ->flatten(1);

        $this->collect($trackers->where('user_id', '!=', null)->groupBy('user_id'));
        $this->collect($trackers->where('user_id', '=', null)->groupBy('ip'));


    }

    public function collect($groups)
    {
        return $groups->map(function ($group, $key) {
            $user = User::find($key);
            $tracker = $this->model->getTracker(...[
                'user' => $user,
                'ip' => $user ? null : $key
            ]);
            Log::info('$tracker:' . $tracker);
            Log::info('$group:' . $group);
            $res = $this->save($tracker, $group);
            if ($user && $this->model instanceof Edition) {
                $this->toLib($user);
            }

            return $res;

        });
    }

    public function save($tracker, $array)
    {

        $progress = (int)ceil($array
            ->pluck('progress') //прогресс по всем трекнутым
            ->pad($this->model->{$this->model->trackerChildRelation}()->count(), 0)// добиваем до общего кол-ва
            ->avg() // profit
        );
        Log::info('$progress' . $progress);

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
