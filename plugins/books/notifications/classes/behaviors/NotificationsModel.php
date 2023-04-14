<?php

namespace Books\Notifications\Classes\Behaviors;

use Model;
use October\Rain\Extension\ExtensionBase;
use RainLab\Notify\Models\Notification;

class NotificationsModel extends ExtensionBase
{
    /**
     * @param Model $model
     */
    public function __construct(protected $model)
    {
        $this->model->morphMany['notifications'] = [
            Notification::class,
            'name' => 'notifiable',
        ];
    }
}
