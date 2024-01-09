<?php

namespace Books\Notifications\Components;

use Books\Notifications\Classes\Contracts\NotificationService;
use Books\Notifications\Classes\NotificationHandlers;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

class NotificationsInHeader extends ComponentBase
{
    use NotificationHandlers;

    protected NotificationService $service;

    /**
     * @return string[]
     */
    public function componentDetails(): array
    {
        return [
            'name' => 'Уведомления в шапке',
            'description' => 'Отображение колокольчика и уведомлений в шапке сайта',
        ];
    }

    /**
     * @return array[]
     */
    public function defineProperties(): array
    {
        return [
            'recordsPerView' => [
                'title' => 'Уведомлений на колокольчике',
                'comment' => 'Количество уведомлений отображаемых при наведении на колокольчик',
                'default' => 11,
            ],
        ];
    }

    /**
     * @return void
     */
    public function init(): void
    {
        if (! Auth::getUser()) {
            return;
        }

        $this->service = app(NotificationService::class);

        $this->page['unreadNotifications'] = $this->service->getCountUnreadNotifications(Auth::getUser()->profile);
    }

    /**
     * @return array
     */
    public function onViewHeaderNotifications(): array
    {
        if (! Auth::getUser()) {
            return [];
        }

        $this->page['headerNotifications'] = $this->service->getSomeNotifications(
            Auth::getUser()->profile,
            (int) $this->property('recordsPerView', 11)
        );

        $this->service->markNotificationsAsRead(Auth::getUser()->profile, $this->page['headerNotifications']);

        return [
            '#notifications-in-header-list' => $this->renderPartial('@list'),
            '#notifications-in-header-list-mobile' => $this->renderPartial('@list-mobile'),
        ];
    }
}
