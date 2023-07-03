<?php

namespace Books\Notifications\Classes\Behaviors;

use Cms\Classes\Controller;
use Cms\Classes\Page;
use Exception;
use Illuminate\Support\Arr;
use October\Rain\Extension\ExtensionBase;
use RainLab\Notify\Models\Notification;
use Twig;

class NotificationModel extends ExtensionBase
{
    public function __construct(protected Notification $notification)
    {
    }

    public function render(array $args = []): string
    {
        $params = array_merge($this->notification->data, $args, ['notification' => $this->notification]);
        $template = Arr::get($params, 'template');
        $templatePath = plugins_path("books/notifications/views/{$template}.twig");

        if (!file_exists($templatePath) || !is_readable($templatePath)) {
            return '';
        }

        $markup = file_get_contents($templatePath);

        try {
            return (new Controller)
                ->getTwig()
                ->createTemplate($markup)
                ->render($params);
        } catch (Exception $e) {
            return Twig::parse($markup, $params);
        }
    }


}
