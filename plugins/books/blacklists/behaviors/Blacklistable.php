<?php

namespace Books\Blacklists\Behaviors;

use Books\Blacklists\Models\CommentsBlacklist;
use Books\Profile\Models\Profile;
use October\Rain\Extension\ExtensionBase;

class Blacklistable extends ExtensionBase
{
    public function __construct(protected Profile $profile)
    {
        /**
         * Comments
         */
        $this->profile->hasManyThrough['comments_blacklisted_for_profiles'] = [
            Profile::class,
            'key' => 'owner_profile_id',
            'through' => CommentsBlacklist::class,
            'throughKey' => 'id',
            'otherKey' => 'id',
            'secondOtherKey' => 'banned_profile_id',
        ];
        $this->profile->hasManyThrough['comments_blacklisted_by'] = [
            Profile::class,
            'key' => 'banned_profile_id',
            'through' => CommentsBlacklist::class,
            'throughKey' => 'id',
            'otherKey' => 'id',
            'secondOtherKey' => 'owner_profile_id',
        ];

        /**
         * Chat
         */
        // todo
    }

    /**
     * @param Profile $banned
     *
     * @return bool
     */
    public function isCommentsBlacklistedFor(Profile $banned): bool
    {
        return $this->profile->comments_blacklisted_for_profiles()
            ->where('banned_profile_id', $banned->id)
            ->exists();
    }

    /**
     * @param Profile $owner
     *
     * @return bool
     */
    public function isCommentsBlacklistedBy(Profile $owner): bool
    {
        return $this->profile->comments_blacklisted_by()
            ->where('owner_profile_id', $owner->id)
            ->exists();
    }

    /**
     * @param Profile $banProfile
     *
     * @return void
     */
    public function blacklistProfileInComments(Profile $banProfile): void
    {
        if (! $this->isCommentsBlacklistedFor($banProfile)) {
            CommentsBlacklist::create([
                'owner_profile_id' => $this->profile->id,
                'banned_profile_id' => $banProfile->id,
            ]);
        }
    }

    /**
     * @param Profile $unBanProfile
     *
     * @return void
     */
    public function unBlacklistProfileInComments(Profile $unBanProfile): void
    {
        if ($this->isCommentsBlacklistedFor($unBanProfile)) {
            CommentsBlacklist::where([
                'owner_profile_id' => $this->profile->id,
                'banned_profile_id' => $unBanProfile->id,
            ])->delete();
        }
    }
}
