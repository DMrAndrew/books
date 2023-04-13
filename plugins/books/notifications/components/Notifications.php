<?php

namespace Books\Notifications\Components;

use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

class Notifications extends ComponentBase
{
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
                'default' => 10,
            ],
        ];
    }

    public function onRun(): void
    {
        if (!Auth::getUser()) {
            return;
        }

        // TODO: получаем список уведомлений
    }
}
