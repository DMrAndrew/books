<?php namespace Books\Profile\Traits;

use Books\Profile\Models\Profile;
use October\Rain\Database\Builder;

trait Subscribable
{

    public function subscribe(Profile $profile): void
    {
        if ($profile->user_id !== $this->user_id) {
            $this->subscribers()->add($profile);
        }
    }

    public function toggleSubscribe(Profile $profile)
    {
        if ($this->hasSubscriber($profile)) {
            $this->unSubscribe($profile);
        } else {
            $this->subscribe($profile);
        }
    }

    public function unSubscribe(Profile $profile)
    {
        if ($profile->user_id !== $this->user_id) {
            $this->subscribers()->remove($profile);
        }
    }

    public function hasSubscriber(Profile $profile): bool
    {
        return $this->subscribers()->where('subscriber_id', '=', $profile->id)->exists();
    }

    public function scopeHasSubscriber(Builder $builder, ?Profile $profile): Builder
    {
        if (!$profile) {
            return $builder;
        }
        return $builder->withExists(['subscribers' => fn($subscribers) => $subscribers->where('subscriber_id', '=', $profile->id)]);
    }

    public function scopeWithSubscriberCount(Builder $builder): Builder
    {
        return $builder->withCount('subscribers');
    }
}
