<?php namespace Books\Shop\Components;

use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Shop\Models\Category;
use Cms\Classes\ComponentBase;

/**
 * ShopLCForm Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class ShopLCForm extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'ShopLCForm Component',
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
        $this->page['categories'] = Category::all();
//        dd($this->page['categories']);
    }

    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('lc-shop-create', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Магазин', url('/lc-shop'));
            $trail->push('Добавление товара');
        });
    }
}
