<?php

namespace Books\Profile\Traits;

use Books\Profile\Models\Profile;
use October\Rain\Database\Builder;

trait Subscribable
{
    public function addSubscribers(Profile $profile): void
    {
        if (! $profile->is($this) && ! $this->hasSubscribers($profile)) {
            $this->subscribers()->add($profile);
        }
    }

    public function removeSubscribers(Profile $profile): void
    {
        if (! $profile->is($this) && $this->hasSubscribers($profile)) {
            $this->subscribers()->remove($profile);
        }
    }

    public function addSubscriptions(Profile $profile): void
    {
        if (! $profile->is($this) && ! $this->hasSubscription($profile)) {
            $this->subscriptions()->add($profile);
        }
    }

    public function removeSubscriptions(Profile $profile): void
    {
        if (! $profile->is($this) && $this->hasSubscription($profile)) {
            $this->subscriptions()->remove($profile);
        }
    }

    public function toggleSubscriptions(Profile $profile)
    {
        if ($this->hasSubscription($profile)) {
            $this->removeSubscriptions($profile);
        } else {
            $this->addSubscriptions($profile);
        }
    }

    public function hasSubscription(Profile $profile): bool
    {
        return (bool) $this->subscriptions()->find($profile);
    }

    public function hasSubscribers(Profile $profile): bool
    {
        return (bool) $this->subscribers()->find($profile);
    }

    public function scopeHasSubscriber(Builder $builder, ?Profile $profile): Builder
    {
        if (! $profile) {
            return $builder;
        }

        return $builder->withExists(['subscribers' => fn ($subscribers) => $subscribers->where('subscriber_id', '=', $profile->id)]);
    }

    public function scopeWithSubscriberCount(Builder $builder): Builder
    {
        return $builder->withCount('subscribers');
    }
}
