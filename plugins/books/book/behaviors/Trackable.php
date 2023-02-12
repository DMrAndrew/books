<?php

namespace Books\Book\Behaviors;

use Books\Book\Models\Tracker;
use Model;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class Trackable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->morphMany['trackers'] = [Tracker::class, 'name' => 'trackable'];
    }

    public function trackByUser(User $user): Tracker
    {
        $tracker = $this->model->trackers()->user($user)->first();
        if (!$tracker) {
            $tracker = $this->model->addTracker($user);
        }

        return $tracker;
    }

    public function addTracker(User $user)
    {
        return $this->model->trackers()->create([
            'user_id' => $user->id,
        ]);
    }


    public function computeProgress(?User $user = null)
    {
        if (!$this->model->trackerChildRelation || !$this->model->hasRelation($this->model->trackerChildRelation)) {
            return false;
        }

        return $this->model
            ->{$this->model->trackerChildRelation}()
            ->with('trackers')->get()
            ->pluck('trackers')
            ->flatten(1)
            ->filter(fn($i) => !$user || $i['user_id'] === $user->id)
            ->groupBy('user_id')
            ->map(function ($trackers, $user_id) {
                $tracker = $this->model->trackByUser(User::find($user_id));

                $progress = (int)floor(
                    $tracker->pluck('progress') //прогресс по всем трекнутым
                    ->pad($this->model->{$this->model->trackerChildRelation}()->count(), 0)// добиваем до общего кол-ва
                    ->avg() // profit
                );
                 $tracker->update([
                    'length' => $trackers->sum('length'),
                    'time' => $trackers->sum('time'),
                    'progress' => $progress
                ]);

                return $progress;

            });
    }
}
