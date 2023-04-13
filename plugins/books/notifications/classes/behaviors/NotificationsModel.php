<?php

namespace Books\Notifications\Classes\Behaviors;

use Model;
use October\Rain\Extension\ExtensionBase;
use RainLab\Notify\Models\Notification;

class NotificationsModel extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->morphMany['notifications'] = [
            Notification::class,
            'name' => 'notifiable',
        ];
    }
}
