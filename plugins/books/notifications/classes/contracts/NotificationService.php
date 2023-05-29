<?php

namespace Books\Notifications\Classes\Contracts;

use Books\Profile\Models\Profile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface NotificationService
{
    /**
     * @param  Profile  $profile
     * @return int
     */
    public function getCountUnreadNotifications(Profile $profile): int;

    /**
     * @param  Profile  $profile
     * @return void
     */
    public function markAllNotificationsAsRead(Profile $profile): void;

    /**
     * @param Profile $profile
     * @param Collection $notifications
     *
     * @return void
     */
    public function markNotificationsAsRead(Profile $profile, Collection $notifications): void;

    /**
     * @param  Profile  $profile
     * @param  int  $limit
     * @return Collection
     */
    public function getUnreadNotifications(Profile $profile, int $limit = 10): Collection;

    /**
     * @param  Profile  $profile
     * @param  string  $activeTab
     * @return array
     */
    public function prepareTabsWithUnreadCount(Profile $profile, string $activeTab = 'all'): array;

    /**
     * @param  Profile  $profile
     * @param  string  $type
     * @param  int  $limit
     * @return LengthAwarePaginator
     */
    public function getNotifications(Profile $profile, string $type = 'all', int $limit = 10): LengthAwarePaginator;
}
