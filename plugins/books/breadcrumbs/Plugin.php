<?php namespace Books\Breadcrumbs;

use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Components\Breadcrumbs;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Cms\Classes\Page;
use Books\Breadcrumbs\Models\Settings;
use RainLab\Translate\Classes\Translator;
use System\Classes\PluginBase;
use System\Classes\PluginManager;
use RainLab\Translate\Models\Message;

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

    /**
     *
     */
    public function boot(): void
    {
        $this->registerBreadcrumbs();
    }

    /**
     *
     */
    public function register(): void
    {
        $this->app->singleton(BreadcrumbsManager::class, BreadcrumbsManager::class);
    }

    /**
     * @return array
     */
    public function registerPermissions(): array
    {
        return [
            'books.breadcrumbs.access_settings' => [
                'label' => 'books.breadcrumbs::lang.plugin.permissions.access_settings',
                'tab'   => 'books.breadcrumbs::lang.settings.label',
            ],
        ];
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
     * @return array
     */
    public function registerSettings(): array
    {
        return [
            'breadcrumbs' => [
                'label'       => 'books.breadcrumbs::lang.settings.label',
                'description' => 'books.breadcrumbs::lang.settings.description',
                'category'    => 'system::lang.system.categories.cms',
                'icon'        => 'icon-globe',
                'class'       => Settings::class,
                'order'       => 500,
                'permissions' => ['books.breadcrumbs.access_settings'],
            ],
        ];
    }

    /**
     * Добавление первой хлебной крошки (главная страница)
     * если в настройках страница не выбрана устанавливаем имя "Главная" и ссылка /
     */
    private function registerBreadcrumbs(): void
    {
        $locale = '';

        if (PluginManager::instance()->hasPlugin('RainLab.Translate')) {
            $locale = Translator::instance()->getLocale();
        }

        try {
            /** @var BreadcrumbsManager $manager */
            $manager = app(BreadcrumbsManager::class);
            $manager->register('home', static function (BreadcrumbsGenerator $trail) use ($locale) {
                $page = Page::find(Settings::get('homepage'));

                if ($page) {
                    $trail->push($page->title, 'javascript:', [
                        'itemid' => url($locale . $page->url)
                    ]);
                    return;
                }

                $trail->push(Message::trans('Home'), 'javascript:', [
                    'itemid' => url($locale . $page?->url)
                ]);
            });
        } catch (DuplicateBreadcrumbException $e) {
            trace_log($e);
        }
    }
}
