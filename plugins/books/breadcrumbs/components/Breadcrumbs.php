<?php namespace Books\Breadcrumbs\Components;

use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Cms\Classes\ComponentBase;

/**
 * Breadcrumbs Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Breadcrumbs extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Breadcrumbs Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     *
     */
    public function onRun(): void
    {
        /** @var BreadcrumbsManager $manager */
        $manager = app(BreadcrumbsManager::class);

        // получим имя страницы для которой генерируем хлебные крошки, имя должно быть указано в настройках страницы
        $name = $this->page->page->breadcrumbs;

        // получаем параметры страницы если они есть, мы их передадим в колбэк и там сможем получить необходимые данные
        $params = $this->controller->getRouter()->getParameters();

        // генерируем крошки только если смогли получить имя страницы для генерации
        if ($name) {
            $this->page['breadcrumbs'] = $manager->generate($name, $params);
        }
    }
}
