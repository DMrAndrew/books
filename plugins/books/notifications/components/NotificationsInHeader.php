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
        $this->service = app(NotificationService::class);
    }

    public function onRun(): void
    {
        if (!Auth::getUser()) {
            return;
        }

        $this->page['unreadNotifications'] = $this->service->getCountUnreadNotifications(Auth::getUser()->profile);
        $this->page['headerNotifications'] = $this->service->getUnreadNotifications(
            Auth::getUser()->profile,
            (int)$this->property('recordsPerView', 11)
        );
    }
}
