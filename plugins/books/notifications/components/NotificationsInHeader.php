<?php

namespace Books\Notifications\Components;

use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

class NotificationsInHeader extends ComponentBase
{
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
                'default' => 4,
            ],
        ];
    }

    public function onRun(): void
    {
        if (!Auth::getUser()) {
            return;
        }

        // TODO: получаем не прочитанные уведомления + счетчик сколько всего не прочитано
    }
}
