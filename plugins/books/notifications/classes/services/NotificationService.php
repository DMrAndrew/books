<?php

namespace Books\Notifications\Classes\Services;

use Books\Notifications\Classes\Contracts\NotificationService as NotificationServiceContract;
use Books\Notifications\Classes\NotificationTypeEnum;
use Books\Profile\Models\Profile;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RainLab\Notify\Models\Notification;
use RainLab\User\Models\User;

class NotificationService implements NotificationServiceContract
{
    /**
     * {@inheritDoc}
     */
    public function getCountUnreadNotifications(Profile $profile): int
    {
        return $this
            ->getBuilder($profile)
            ->applyUnread()
            ->count();
    }

    /**
     * {@inheritDoc}
     */
    public function markAllNotificationsAsRead(Profile $profile): void
    {
        $this
            ->getBuilder($profile)
            ->applyUnread()
            ->update(['read_at' => Carbon::now()]);
    }

    /**
     * {@inheritDoc}
     */
    public function getUnreadNotifications(Profile $profile, int $limit = 10): Collection
    {
        return $this
            ->getBuilder($profile)
            ->applyUnread()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
    public function getSomeNotifications(Profile $profile, int $limit = 10): Collection
    {
        return $this
            ->getBuilder($profile)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function prepareTabsWithUnreadCount(Profile $profile, string $activeTab = 'all'): array
    {
        $unreadCounts = $this
            ->getBuilder($profile)
            ->applyUnread()
            ->get(['id', 'type']);

        $tabs = [
            'all' => [
                'label' => 'Все',
                'count' => $unreadCounts->count(),
                'active' => $activeTab === 'all',
            ],
        ];

        $unreadCounts = $unreadCounts->countBy('type');

        foreach (NotificationTypeEnum::cases() as $type) {
            $tabs[$type->value] = [
                'label' => $type->label(),
                'count' => (int) $unreadCounts->get($type->value),
                'active' => $type->value === $activeTab,
            ];
        }

        return $tabs;
    }

    /**
     * {@inheritDoc}
     */
    public function getNotifications(Profile $profile, string $type = 'all', int $limit = 10): LengthAwarePaginator
    {
        $builder = $this->getBuilder($profile);

        if ($type !== 'all') {
            $builder->where('type', $type);
        }

        $paginator = $builder
            ->orderByDesc('created_at')
            ->paginate($limit);

        if ($type !== 'all') {
            $paginator->appends('type', $type);
        }

        return $paginator;
    }

    /**
     * @param  Profile  $profile
     * @return Builder
     */
    private function getBuilder(Profile $profile): Builder
    {
        return Notification::query()
            ->where(static function (Builder $query) use ($profile): void {
                $query
                    ->whereHasMorph(
                        'notifiable',
                        Profile::class,
                        static function (Builder $query) use ($profile): void {
                            $query->where('id', $profile->getKey());
                        }
                    )
                    ->orWhereHasMorph(
                        'notifiable',
                        User::class,
                        static function (Builder $query) use ($profile): void {
                            $query->where('id', $profile->user->getKey());
                        }
                    );
            });
    }

    /**
     * @param Profile $profile
     * @param Collection $notifications
     *
     * @return void
     */
    public function markNotificationsAsRead(Profile $profile, Collection $notifications): void
    {
        if ($notifications->count() > 0) {
            $this
                ->getBuilder($profile)
                ->applyUnread()
                ->whereIn('id', $notifications->pluck('id'))
                ->update(['read_at' => Carbon::now()]);
        }
    }
}
