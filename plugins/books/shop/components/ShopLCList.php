<?php namespace Books\Shop\Components;

use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Cms\Classes\ComponentBase;

/**
 * ShopLCList Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class ShopLCList extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'ShopLCList Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->prepareVals();
        $this->registerBreadcrumbs();
    }

    private function prepareVals()
    {
    }

    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('lc-shop', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Магазин', url('/lc-shop'));
        });
    }
}
