<?php namespace Books\Shop\Components;

use Books\Profile\Models\Profile;
use Books\Shop\Models\Order;
use Books\Shop\Models\OrderItems;
use Books\Shop\Models\Product;
use Cms\Classes\ComponentBase;
use Illuminate\Database\Eloquent\Collection;
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
        $this->prepareVals();
    }

    public function getOrderItems(): Collection
    {
        return OrderItems::where('buyer_id', $this->userProfile->getKey())->whereNull('order_id')->get();
    }

    private function prepareVals()
    {
        $orderItems = $this->getOrderItems();
        $sellers['usernames'] = Profile::whereIn('id', $orderItems->pluck('seller_id')->unique())->get()->pluck('username', 'id')->toArray();
        $orderItems->groupBy('seller_id')->each(function ($items, $key) use (&$sellers) {
            $itemsSum = 0;
            foreach ($items as $item) {
                if ($item->product->quantity != 0 || $item->quantity < $item->product->quantity) {
                    $itemsSum += $item->price * $item->quantity;
                    $sellers['hasDisabledProduct'][$key] = $sellers['hasDisabledProduct'][$key] ?? false;
                } else {
                    $sellers['hasDisabledProduct'][$key] = true;
                }
            }
            $sellers['amount'][$key] = $itemsSum;
        });
        $this->page['orderItemsCount'] = $orderItems->count();
        $this->page['orderItems'] = $orderItems->groupBy('seller_id');
        $this->page['sellers'] = $sellers;
    }

    public function onAddToBasket()
    {
        $orderItem = OrderItems::where('product_id', (int)post('productId'))
                        ->where('buyer_id', $this->userProfile->getKey())
                        ->whereNull('order_id')
                        ->first();
        if ($orderItem) {
            $orderItem->quantity = $orderItem->quantity + 1;
            $orderItem->save();
        } else {
            $product = Product::findOrFail((int)post('productId'));
            OrderItems::create([
                'buyer_id' => $this->userProfile->getKey(),
                'seller_id' => $product->seller_id,
                'product_id' => $product->getKey(),
                'quantity' => 1,
                'price' => $product->price,
            ]);
        }

        $orderItemsCount = $this->getOrderItems();

        return [
            '#basketInHeader' => $this->renderPartial('@inHeader', [
                'orderItemsCount' => $orderItemsCount->count()
            ]),
            '#buy-btn-' . $product->id => $this->renderPartial('@buyBtn', [
                'product' => $product,
                'productsInBasket' => OrderItems::where('buyer_id', Auth::getUser()->profile->getKey())
                    ->whereNull('order_id')
                    ->get()
                    ->pluck('product_id'),
            ]),
        ];
    }

    public function onAddQuantity()
    {
        $orderItem = OrderItems::findOrFail((int)post('orderItemId'));
        $orderItem->quantity = $orderItem->quantity + 1;
        $orderItem->save();

        $this->prepareVals();
        return [
            '#basket-conteiner' => $this->renderPartial('@default'),
        ];
    }

    public function onReduceQuantity()
    {
        $orderItem = OrderItems::findOrFail((int)post('orderItemId'));
        $orderItem->quantity = $orderItem->quantity - 1;
        $orderItem->save();
        $this->prepareVals();
        return [
            '#basket-conteiner' => $this->renderPartial('@default'),
        ];
    }

    public function onRemoveProduct()
    {
        OrderItems::destroy((int)post('orderItemId'));
        $this->prepareVals();
        return [
            '#basket-conteiner' => $this->renderPartial('@default'),
        ];
    }
}
