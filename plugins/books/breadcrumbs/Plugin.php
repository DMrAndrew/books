<?php namespace Books\Breadcrumbs;

use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Components\Breadcrumbs;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails(): array
    {
        return [
            'name'        => 'books.breadcrumbs::lang.plugin.name',
            'description' => 'books.breadcrumbs::lang.plugin.description',
            'author'      => 'Mobecan',
            'icon'        => 'icon-ellipsis-h',
        ];
    }

    public function boot(): void
    {
        $this->registerBreadcrumbs();
    }

    public function register(): void
    {
        $this->app->singleton(BreadcrumbsManager::class, BreadcrumbsManager::class);
    }

    /**
     * @return array
     */
    public function registerPermissions(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function registerComponents(): array
    {
        return [
            Breadcrumbs::class => 'Breadcrumbs',
        ];
    }

    /**
     * Добавление первой хлебной крошки (главная страница)
     * если в настройках страница не выбрана устанавливаем имя "Главная" и ссылка /
     */
    private function registerBreadcrumbs(): void
    {
        try {
            /** @var BreadcrumbsManager $manager */
            $manager = app(BreadcrumbsManager::class);
            $manager->register('home', static function (BreadcrumbsGenerator $trail) {
                $trail->push('Главная', '/');
            });
            $manager->register('lc', static function (BreadcrumbsGenerator $trail) {
                $trail->parent('home');
                $trail->push('Личный кабинет', '/lc-profile');
            });
            $manager->register('commercial_cabinet', static function (BreadcrumbsGenerator $trail) {
                $trail->parent('home');
                $trail->push('Коммерческий кабинет', '/lc-commercial');
            });
        } catch (DuplicateBreadcrumbException $e) {
            trace_log($e);
        }
    }
}
