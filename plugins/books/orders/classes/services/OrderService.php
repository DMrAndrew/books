<?php
declare(strict_types=1);

namespace Books\Orders\Classes\Services;

use Books\Book\Models\Donation;
use Books\Book\Models\Promocode;
use Books\Orders\Classes\Contracts\OrderService as OrderServiceContract;
use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Models\Order;
use Books\Orders\Models\OrderProduct;
use Books\Orders\Models\OrderPromocode;
use Carbon\Carbon;
use October\Rain\Database\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

class OrderService implements OrderServiceContract
{
    /**
     * @param User $user
     * @param array $products
     *
     * @return Order
     */
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

    /**
     * @param Order $order
     *
     * @return int
     */
    public function calculateAmount(Order $order): int
    {
        // products
        $initialOrderAmount = $order->products->sum('amount');

        // promocodes
        $appliedPromocodesAmount = 0;
        $order->promocodes->each(function($orderPromocode) use (&$appliedPromocodesAmount) {
            $appliedPromocodesAmount += (int) $orderPromocode->promocode->promoable->priceTag()->price();
        });

        // todo ? book discounts

        return max(($initialOrderAmount - $appliedPromocodesAmount), 0);
    }

    public function updateOrderstatus(Order $order, OrderStatusEnum $status): bool
    {
        return $order->update(['status' => $status->value]);
    }

    public function approveOrder(Order $order): bool
    {
        // выдать покупателю товар

        // создать награды
            // books_book_award_books

        // пополнить баланс автора

        // добавить историю транзакций

        return true;
    }

    public function cancelOrder(Order $order): bool
    {
        return true;
    }

    /**
     * @param Order $order
     * @param string $code
     *
     * @return bool
     */
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
        OrderPromocode::create([
            'order_id' => $order->id,
            'promocode_id' => $promocode->id,
        ]);

        // activate promocode
        $promocode->update([
            'is_activated' => true,
            'activated_at' => Carbon::now(),
            'user_id' => Auth::getUser()->id,
        ]);

        return true;
    }

    /**
     * @param Order $order
     * @param Collection $awards
     *
     * @return void
     */
    public function applyAwards(Order $order, Collection $awards): void
    {
        $appliedAwards = $order->awards()->get();

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

    /**
     * @param Order $order
     * @param int $donateAmount
     *
     * @return void
     */
    public function applyAuthorSupport(Order $order, int $donateAmount): void
    {
        $appliedDonations = $order->donations()->get();
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
