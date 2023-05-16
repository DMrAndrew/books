<?php
declare(strict_types=1);

namespace Books\Orders\Classes\Services;

use Books\Book\Models\AwardBook;
use Books\Book\Models\Donation;
use Books\Book\Models\Edition;
use Books\Book\Models\Promocode;
use Books\Book\Models\UserBook;
use Books\Orders\Classes\Contracts\OrderService as OrderServiceContract;
use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Models\BalanceDeposit as DepositModel;
use Books\Orders\Models\Order;
use Books\Orders\Models\OrderProduct;
use Books\Orders\Models\OrderPromocode;
use Carbon\Carbon;
use Db;
use Exception;
use October\Rain\Support\Collection;
use October\Rain\Database\Model;
use RainLab\User\Models\User;

class OrderService implements OrderServiceContract
{
    /**
     * @param User $user
     *
     * @return Order
     */
    public function createOrder(User $user): Order
    {
        return Order::create(['user_id' => $user->id]);
    }

    /**
     * @param Order $order
     * @param Collection $products
     *
     * @return bool
     */
    public function addProducts(Order $order, Collection $products): bool
    {
        foreach ($products as $product) {
            if ($this->isProductOrderable($product)) {
                $orderProduct = new OrderProduct();
                $orderProduct->orderable()->associate($product);
                $orderProduct->order_id = $order->id;
                $orderProduct->initial_price = $product->price;
                $orderProduct->amount = $product->priceTag()->price() ?? $product->price;
                $orderProduct->save();
            }
        }

        return true;
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
        $depositAmount = $order->deposits->sum('amount');

        return max(($orderAmount - $awardsAmount - $depositAmount), 0);
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
     * @throws Exception
     */
    public function approveOrder(Order $order): bool
    {
        // выдать покупателю товар(ы)
        $this->giveCustomerProducts($order);

        // добавить награды книгам
        $this->addAwardsToEditions($order);

        // пополнить баланс автора
        $this->updateAuthorsBalance($order);

        // зачислить пополнение баланса пользователю
        $this->transferDepositToUserBalance($order);

        // добавить историю операций
        // todo

        return true;
    }

    /**
     * @param Model $product
     *
     * @return bool
     */
    private function isProductOwnable(mixed $product): bool
    {
        if ($product instanceof Model) {
            return in_array('customers', array_keys($product->morphMany));
        }

        return false;
    }

    /**
     * @param Model $product
     *
     * @return bool
     */
    private function isProductOrderable(mixed $product): bool
    {
        if ($product instanceof Model) {
            return in_array('products', array_keys($product->morphMany));
        }

        return false;
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
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
        return DB::transaction( function() use ($order, $code) {
            // get promocode
            $promocode = Promocode::where('code', $code)->notActivated()->first();

            if (!$promocode) {
                return false;
            }

            // check promocode product
            if (!$this->isPromoableProductInOrderProductsList($order, $promocode)) {
                return false;
            }

            // apply promocode
            $this->activatePromocode($order, $promocode);

            return true;
        });
    }

    /**
     * @param Order $order
     * @param Promocode $promocode
     *
     * @return bool
     */
    public function isPromoableProductInOrderProductsList(Order $order, Promocode $promocode): bool
    {
        /** @var Promocode $promocodeProduct */
        $promocodeProduct = $promocode->promoable;

        $promoableProductInOrder = $order->products
            ->where('orderable_type', $promocodeProduct::class)
            ->where('orderable_id', $promocodeProduct->id)
            ->first();

        return $promoableProductInOrder->exists;
    }

    public function activatePromocode(Order $order, Promocode $promocode): void
    {
        OrderPromocode::create([
            'order_id' => $order->id,
            'promocode_id' => $promocode->id,
        ]);

        $promocode->update([
            'is_activated' => true,
            'activated_at' => Carbon::now(),
            'user_id' => $order->user_id,
        ]);
    }

    /**
     * @param Order $order
     * @param Collection $awards
     *
     * @return void
     */
    public function applyAwards(Order $order, mixed $awards): void
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

    /**
     * @param Order $order
     *
     * @return string
     */
    public function getOrderSuccessRedirectPage(Order $order): string
    {
        // get book page
        $book = $order->products()
            ->where('orderable_type', [Edition::class])
            ->first()
            ?->orderable
            ?->book;

        if ($book) {
            return url('book-card', ['book_id' => $book->id]);
        }

        return url('/');
    }

    /**
     * @param Order $order
     *
     * @return string
     */
    public function getOrderErrorRedirectPage(Order $order): string
    {
        return $this->getOrderSuccessRedirectPage($order);
    }

    public function addDeposit(Order $order, int $depositAmount): void
    {
        if ($depositAmount > 0) {
            $deposit = DepositModel::create([
                'amount' => $depositAmount,
            ]);

            $orderProduct = new OrderProduct();
            $orderProduct->orderable()->associate($deposit);
            $orderProduct->order_id = $order->id;
            $orderProduct->amount = $depositAmount;
            $orderProduct->save();
        }
    }

    /**
     * @param Order $order
     *
     * @return void
     */
    private function giveCustomerProducts(Order $order): void
    {
        $user = $order->user;

        foreach ($order->products as $orderProduct) {
            $product = $orderProduct->orderable;

            if ($this->isProductOwnable($product)) {
                $newUserOwning = new UserBook();
                $newUserOwning->user_id = $user->id;
                $newUserOwning->ownable()->associate($product);
                $newUserOwning->save();
            }
        }
    }

    /**
     * @param Order $order
     *
     * @return void
     */
    private function addAwardsToEditions(Order $order): void
    {
        $user = $order->user;

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
    }

    /**
     * @param Order $order
     *
     * @return void
     * @throws Exception
     */
    private function updateAuthorsBalance(Order $order): void
    {
        $authorRewardAmount = $this->calculateAuthorsOrderReward($order);

        if ($authorRewardAmount > 0) {
            $author = $order->products()
                ->where('orderable_type', [Edition::class])
                ->first()
                ?->orderable
                ?->book
                ?->author;

            if (!$author) {
                throw new Exception("Unable to resolve product Author for order #{$order->id}");
            }

            $author->profile->user->proxyWallet()->deposit($this->calculateAuthorsOrderReward($order));
        }
    }

    /**
     * @param Order $order
     *
     * @return void
     */
    private function transferDepositToUserBalance(Order $order): void
    {
        $depositAmount = $order->deposits->sum('amount');

        if ($depositAmount > 0) {
            $order->user->proxyWallet()->deposit($depositAmount);
        }
    }
}
