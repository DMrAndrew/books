<?php namespace Books\Shop;

use Backend;
use Books\Profile\Models\Profile;
use Books\Shop\Behaviors\HasProduct;
use Books\Shop\Components\Basket;
use Books\Shop\Components\BasketInHeader;
use Books\Shop\Components\MainCatalog;
use Books\Shop\Components\ProductDetailPageComponent;
use Books\Shop\Components\ShopCategorySidebar;
use Books\Shop\Components\ShopLCForm;
use Books\Shop\Components\ShopLCList;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    public $require = [
        'RainLab.User',
        'Books.Breadcrumbs',
        'Books.Profile',
        'Books.Fileuploader',
    ];

    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Shop',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-shopping-bag'
        ];
    }

    /**
     * register method, called when the plugin is first registered.
     */
    public function register()
    {
        //
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        Profile::extend(function (Profile $model) {
            $model->implementClassWith(HasProduct::class);
        });
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            ShopLCList::class => 'ShopLCList',
            ShopLCForm::class => 'ShopLCForm',
            MainCatalog::class => 'MainCatalog',
            ShopCategorySidebar::class => 'ShopCategorySidebar',
            ProductDetailPageComponent::class => 'ProductDetailPageComponent',
            BasketInHeader::class => 'BasketInHeader',
            Basket::class => 'Basket',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return [
            'books.shop.permission' => [
                'tab' => 'Shop',
                'label' => 'Shop permission'
            ],
        ];
    }

    /**
     * registerNavigation used by the backend.
     */
    public function registerNavigation()
    {
        return [
            'shop' => [
                'label' => 'Магазин',
                'icon' => 'icon-shopping-bag',
                'permissions' => ['books.shop.*'],
                'order' => 500,
                'sideMenu' => [
                    'categories' => [
                        'label' => 'Категории',
                        'icon' => 'icon-list-ul',
                        'url' => Backend::url('books/shop/categories'),
                        'permissions' => ['books.shop.*'],
                    ],
                    'products' => [
                        'label' => 'Товары',
                        'icon' => 'icon-shopping-basket',
                        'url' => Backend::url('books/shop/products'),
                        'permissions' => ['books.shop.*'],
                    ]
                ]
            ],
        ];
    }

}
