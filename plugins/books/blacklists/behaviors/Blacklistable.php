<?php

namespace Books\Blacklists\Behaviors;

use Books\Blacklists\Models\ChatBlacklist;
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
        $this->profile->hasManyThrough['profiles_blacklisted_in_comments'] = [
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
        $this->profile->hasManyThrough['profiles_blacklisted_in_chat'] = [
            Profile::class,
            'key' => 'owner_profile_id',
            'through' => ChatBlacklist::class,
            'throughKey' => 'id',
            'otherKey' => 'id',
            'secondOtherKey' => 'banned_profile_id',
        ];
        $this->profile->hasManyThrough['chat_blacklisted_by'] = [
            Profile::class,
            'key' => 'banned_profile_id',
            'through' => ChatBlacklist::class,
            'throughKey' => 'id',
            'otherKey' => 'id',
            'secondOtherKey' => 'owner_profile_id',
        ];
    }

    /**
     * @param Profile $banned
     *
     * @return bool
     */
    public function isCommentsBlacklistedFor(Profile $banned): bool
    {
        return $this->profile->profiles_blacklisted_in_comments()
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
    public function blackListCommentsFor(Profile $banProfile): void
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
    public function unBlackListCommentsFor(Profile $unBanProfile): void
    {
        if ($this->isCommentsBlacklistedFor($unBanProfile)) {
            CommentsBlacklist::where([
                'owner_profile_id' => $this->profile->id,
                'banned_profile_id' => $unBanProfile->id,
            ])->delete();
        }
    }
}
