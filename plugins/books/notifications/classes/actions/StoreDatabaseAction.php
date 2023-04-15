<?php

namespace Books\Notifications\Classes\Actions;

use ApplicationException;
use Cms\Classes\Controller;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use October\Rain\Support\Facades\Twig;
use RainLab\Notify\NotifyRules\SaveDatabaseAction;
use Ramsey\Uuid\Uuid;

class StoreDatabaseAction extends SaveDatabaseAction
{
    /**
     * @return string[]
     */
    public function actionDetails(): array
    {
        return [
            'name' => 'Сохранить в базу данных',
            'description' => 'Записывает уведомление в базу данных',
            'icon' => 'icon-database',
        ];
    }

    /**
     * @return bool
     */
    public function defineFormFields(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->getActionDescription();
    }

    /**
     * @param $params
     * @return void
     *
     * @throws ApplicationException
     */
    public function triggerAction($params): void
    {
        if (
            (! $recipients = Arr::get($params, 'recipients')) ||
            (! $recipients instanceof Collection)
        ) {
            throw new ApplicationException('Ошибка в параметрах уведомления');
        }

        $recipients
            ->each(fn ($recipient) => $recipient
                ->notifications()
                ->create([
                    'id' => Uuid::uuid4()->toString(),
                    'event_type' => $this->host->notification_rule->getEventClass(),
                    'icon' => $this->getParam($params, 'icon'),
                    'type' => $this->getParam($params, 'type'),
                    'body' => $this->prepareBody($params),
                    'data' => $this->getPrepareData($params, $recipient),
                    'read_at' => null,
                ]));
    }

    /**
     * @param  array  $params
     * @param  string  $param
     * @return string|null
     */
    protected function getParam(array $params, string $param): ?string
    {
        return $this->host->$param ?? Arr::get($params, $param);
    }

    /**
     * @param $params
     * @return string
     *
     * @throws ApplicationException
     */
    protected function prepareBody($params): string
    {
        $template = $this->getParam($params, 'template');
        $templatePath = plugins_path("books/notifications/views/{$template}.twig");

        if (! file_exists($templatePath) || ! is_readable($templatePath)) {
            throw new ApplicationException('Ошибка формирования тела уведомления');
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

    /**
     * @param  array  $params
     * @param    $recipient
     * @return array
     */
    protected function getPrepareData(array $params, $recipient): array
    {
        // удалим информацию о всех получателях
        Arr::forget($params, 'recipients');

        // запишем информацию о получателе
        Arr::set($params, 'recipient', $recipient);

        return $params;
    }
}
