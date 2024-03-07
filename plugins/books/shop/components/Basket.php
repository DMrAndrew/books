<?php namespace Books\Shop\Components;

use Books\Profile\Models\Profile;
use Books\Shop\Models\OrderItems;
use Books\Shop\Models\Product;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * Basket Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Basket extends ComponentBase
{
    private $userProfile;

    public function componentDetails()
    {
        return [
            'name' => 'Basket Component',
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
        $this->userProfile = Auth::getUser()->profile;
        $orderItems = OrderItems::where('buyer_id', $this->userProfile->getKey())->whereNull('order_id')->get();
        $sellers['usernames'] = Profile::whereIn('id', $orderItems->pluck('seller_id')->unique())->get()->pluck('username', 'id')->toArray();
        $orderItems->groupBy('seller_id')->each(function ($items, $key) use (&$sellers) {
            $sellers['amount'][$key] = $items->sum('price');
        });
        $this->page['orderItemsCount'] = $orderItems->count();
        $this->page['orderItems'] = $orderItems->groupBy('seller_id');
        $this->page['sellers'] = $sellers;
    }

    public function onAddToBasket()
    {
        $product = Product::findOrFail((int)post('productId'));
        OrderItems::create([
            'buyer_id' => $this->userProfile->getKey(),
            'seller_id' => $product->seller_id,
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'price' => $product->price,
        ]);
        $orderItemsCount = OrderItems::where('buyer_id', $this->userProfile->getKey())->whereNull('order_id')->count();

        return [
            '#basketInHeader' => $this->renderPartial('@inHeader', [
                'orderItemsCount' => $orderItemsCount
            ]),
        ];
    }
}
