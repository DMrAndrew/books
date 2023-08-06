<?php

namespace Books\Notifications\Classes\Behaviors;

use Books\Comments\Models\Comment;
use Books\Notifications\Classes\Events\CommentReplied;
use Cms\Classes\Controller;
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
        $params = array_merge($this->notification->data, $args, ['notification' => $this->notification], $this->loadData());
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

    public function loadData(): array
    {

        return match ($this->notification->event_type) {
            CommentReplied::class => $this->notification->data['comment']['id'] ?? false ? [
                'comment' => Comment::query()->withTrashed()->with(['parent' => fn($p) => $p->withTrashed()])->find($this->notification->data['comment']['id'] ?? null)
            ] : [],
            default => []
        };
    }


}
