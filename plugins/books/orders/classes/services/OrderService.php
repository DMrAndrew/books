<?php
declare(strict_types=1);

namespace Books\Orders\Classes\Services;

use Books\Book\Models\AwardBook;
use Books\Book\Models\Donation;
use Books\Book\Models\Promocode;
use Books\Book\Models\UserBook;
use Books\Orders\Classes\Contracts\OrderService as OrderServiceContract;
use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Models\Order;
use Books\Orders\Models\OrderProduct;
use Books\Orders\Models\OrderPromocode;
use Carbon\Carbon;
use October\Rain\Database\Collection;
use October\Rain\Database\Model;
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

    /**
     * @param Order $order
     *
     * @return int
     */
    public function calculateAuthorsOrderReward(Order $order): int
    {
        $orderAmount = $this->calculateAmount($order);
        $awardsAmount = $order->awards->sum('amount');

        return max(($orderAmount - $awardsAmount), 0);
    }

    /**
     * @param Order $order
     * @param OrderStatusEnum $status
     *
     * @return bool
     */
    public function updateOrderstatus(Order $order, OrderStatusEnum $status): bool
    {
        return $order->update(['status' => $status->value]);
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function approveOrder(Order $order): bool
    {
        $user = $order->user;

        // выдать покупателю товар(ы)
        foreach ($order->products as $orderProduct) {
            $product = $orderProduct->orderable;

            if ($this->isProductOwnable($product)) {
                $newUserOwning = new UserBook();
                $newUserOwning->user_id = $user->id;
                $newUserOwning->ownable()->associate($product);
                $newUserOwning->save();
            }
        }

        // добавить награды книгам
        $orderAwards = $order->awards;
        foreach ($orderAwards as $orderAward) {
            $award = $orderAward->orderable;

            // награду на каждый товар заказа (в перспективе)
            foreach ($order->products as $orderProduct) {
                $product = $orderProduct->orderable;

                if (isset($product->book)) {
                    AwardBook::create([
                        'user_id' => $user->id,
                        'award_id' => $award->id,
                        'book_id' => $product->book->id,
                    ]);
                }
            }
        }

        // пополнить баланс автора
        $user->proxyWallet()->deposit($this->calculateAuthorsOrderReward($order));

        // добавить историю операций

        return true;
    }

    /**
     * @param Model $product
     *
     * @return bool
     */
    private function isProductOwnable(Model $product): bool
    {
        if ($product->morphMany == null || !is_array($product->morphMany)) {
            return false;
        }

        if (empty($product->morphMany)) {
            return false;
        }

        return in_array('customers', array_keys($product->morphMany));
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

    public function getOrderRedirectPage(Order $order): string
    {

    }

    public function updateAuthorsBalance(Order $order): void
    {
        // TODO: Implement updateAuthorsBalance() method.
    }
}
