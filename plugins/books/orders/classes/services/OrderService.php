<?php
declare(strict_types=1);

namespace Books\Orders\Classes\Services;

use Books\Book\Models\Award;
use Books\Book\Models\Donation;
use Books\Book\Models\Promocode;
use Books\Orders\Classes\Contracts\OrderService as OrderServiceContract;
use Books\Orders\Models\Order;
use Books\Orders\Models\OrderProduct;
use Books\Orders\Models\OrderPromocode;
use October\Rain\Database\Collection;
use RainLab\User\Models\User;

class OrderService implements OrderServiceContract
{
    public function createOrder(User $user, array $products): Order
    {
        $order = new Order();
        $order->user = $user;
        $order->save();

        foreach ($products as $product) {
            $orderProduct = new OrderProduct();
            $orderProduct->orderable()->associate($product);
            $orderProduct->order_id = $order->id;
            $orderProduct->initial_price = $product->price;
            $orderProduct->amount = $product->priceTag()->price() ?? $product->price;
            $orderProduct->save();
        }

        return $order;
    }

    public function calculateAmount(Order $order): int
    {
        return $order->products->sum('amount');
    }

    public function payOrderByTransaction(Order $order): bool
    {
        // TODO: Implement payOrderByTransaction() method.
    }

    public function payOrderFromBalance(Order $order): bool
    {
        // TODO: Implement payOrderFromBalance() method.
    }

    public function approveOrder(Order $order): bool
    {
        // TODO: Implement approveOrder() method.
    }

    public function cancelOrder(Order $order): bool
    {
        // TODO: Implement cancelOrder() method.
    }

    public function applyPromocode(Order $order, string $code): bool
    {
        // get promocode
        $promocode = Promocode::query()
            ->notActivated()
            ->where('code', $code)
            ->first();

        if (!$promocode) {
            return false;
        }

        // check promocode product
        $promocodeProduct = $promocode->promoable;

        $promoableProductInOrder = $order->products
            ->where('orderable_type', $promocodeProduct::class)
            ->where('orderable_id', $promocodeProduct->id)
            ->first();

        if (!$promoableProductInOrder) {
            return false;
        }

        // apply promocode
        $appliedPromocode = OrderPromocode::create([
            'order_id' => $order->id,
            'promocode_id' => $promocode->id,
        ]);

        return true;
    }

    public function applyAwards(Order $order, Collection $awards): void
    {
        $appliedAwards = $order->products()->whereHasMorph('orderable', [Award::class])->get();

        $appliedAwards->each(function($appliedAward) {
            $appliedAward->delete();
        });

        foreach ($awards as $award) {
            $orderProduct = new OrderProduct();
            $orderProduct->orderable()->associate($award);
            $orderProduct->order_id = $order->id;
            $orderProduct->initial_price = $award->price;
            $orderProduct->amount = $award->price;
            $orderProduct->save();
        }
    }

    public function applyAuthorSupport(Order $order, int $donateAmount): void
    {
        $appliedDonations = $order->products()->whereHasMorph('orderable', [Donation::class])->get();
        $appliedDonations->each(function($appliedDonation) {
            $appliedDonation->delete();
        });

        if ($donateAmount > 0) {
            $donation = Donation::create(['amount' => $donateAmount]);

            $orderProduct = new OrderProduct();
            $orderProduct->orderable()->associate($donation);
            $orderProduct->order_id = $order->id;
            $orderProduct->amount = $donateAmount;
            $orderProduct->save();
        }
    }

    public function updateAuthorsBalance(Order $order): void
    {
        // TODO: Implement updateAuthorsBalance() method.
    }
}
