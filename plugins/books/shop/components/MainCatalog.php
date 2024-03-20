<?php namespace Books\Shop\Components;

use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Shop\Models\OrderItems;
use Books\Shop\Models\Product;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * MainCatalog Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class MainCatalog extends ComponentBase
{
    private $user;

    public function componentDetails()
    {
        return [
            'name' => 'MainCatalog Component',
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
        $this->addComponent(Basket::class, 'Basket');

        $this->user = Auth::getUser();
        $this->prepareVals();
        $this->registerBreadcrumbs();
    }

    private function prepareVals()
    {
        $orderItems = OrderItems::whereNull('order_id');
        $products = Product::where('quantity', '>', 0);
        if ($this->user) {
            $products->where('seller_id', '!=', $this->user->getKey());
            $orderItems->where('buyer_id', $this->user->profile->getKey());
        }
        $this->page['categoryId'] = $this->getController()->getRouter()->getParameter('category_id') ?? null;
        if ($this->page['categoryId']) {
            $products->where('category_id', $this->page['categoryId']);
        }
        $this->page['products'] = $products->orderByDesc('created_at')->paginate(6);
        $this->page['productsInBasket'] = $orderItems->get()->pluck('product_id');
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
