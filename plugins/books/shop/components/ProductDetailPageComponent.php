<?php namespace Books\Shop\Components;

use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Comments\Components\Comments;
use Books\Shop\Models\Product;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * ProductDetailPageComponent Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class ProductDetailPageComponent extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'ProductDetailPageComponent Component',
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
        $product = Product::findOrFail($this->getController()->getRouter()->getParameter('product_id'));
        $this->page['product'] = $product;
        $comments = $this->addComponent(Comments::class, 'comments');
        $comments->bindModel($product);
        $comments->bindModelOwner($product->seller);
    }

    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('shop', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('/');
            $trail->push('Магазин', url('/shop'));
        });
    }
}
