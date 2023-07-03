<?php

namespace Books\Notifications\Components;

use Books\Notifications\Classes\Contracts\NotificationService;
use Books\Notifications\Classes\NotificationHandlers;
use Books\Notifications\Classes\NotificationTypeEnum;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

class Notifications extends ComponentBase
{
    use NotificationHandlers;

    protected NotificationService $service;

    /**
     * @return string[]
     */
    public function componentDetails(): array
    {
        return [
            'name' => 'Уведомления',
            'description' => 'Отображение списка всех уведомлений',
        ];
    }

    /**
     * @return array[]
     */
    public function defineProperties(): array
    {
        return [
            'recordsPerPage' => [
                'title' => 'Уведомлений на странице',
                'comment' => 'Количество уведомлений отображаемых на одной странице',
                'default' => 16,
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

    /**
     * @return void
     */
    public function onRun(): void
    {
        $this->prepareVars();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function onMarkAllNotificationsAsRead()
    {
        if (!Auth::getUser()) {
            return;
        }

        $this->service->markAllNotificationsAsRead(Auth::getUser()->profile);

        return redirect()->back();
    }

    /**
     * @return array
     */
    public function onAjaxLoad(): array
    {
        $this->prepareVars();

        return [
            '.tabs_list' => $this->renderPartial('Notifications::tabs'),
            '.notifications_list' => $this->renderPartial('Notifications::notifications'),
        ];
    }

    /**
     * @return void
     */
    protected function prepareVars(): void
    {
        if (!Auth::getUser()) {
            return;
        }

        $this->page['tabs'] = $this->service->prepareTabsWithUnreadCount(
            Auth::getUser()->profile,
            $this->getTabType()
        );

        $this->page['notifications'] = $this->service->getNotifications(
            Auth::getUser()->profile,
            $this->getTabType(),
            (int)$this->property('recordsPerPage', 16)
        );
    }

    public function getTabType(): string
    {
        return NotificationTypeEnum::tryFrom(post('type') ?? $this->param('type'))?->value ?? 'all';
    }
}
