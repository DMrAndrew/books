<?php
declare(strict_types=1);

namespace Books\Orders\Classes\Services;

use ApplicationException;
use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Models\AwardBook;
use Books\Book\Models\Book;
use Books\Book\Models\Donation;
use Books\Book\Models\Promocode;
use Books\Orders\Classes\Contracts\OrderService as OrderServiceContract;
use Books\Orders\Classes\Contracts\ProductInterface;
use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Models\BalanceDeposit as DepositModel;
use Books\Orders\Models\Order;
use Books\Orders\Models\OrderProduct;
use Books\Orders\Models\OrderPromocode;
use Books\Profile\Models\Profile;
use Books\Profile\Services\OperationHistoryService;
use Carbon\Carbon;
use Db;
use Exception;
use October\Rain\Support\Collection;
use October\Rain\Database\Model;
use RainLab\User\Models\User;
use Books\Profile\Contracts\OperationHistoryService as OperationHistoryServiceContract;

class OrderService implements OrderServiceContract
{
    private OperationHistoryServiceContract $operationHistoryService;

    public function __construct()
    {
        $this->operationHistoryService = app(OperationHistoryService::class);
    }

    /**
     * @param User $user
     *
     * @return Order
     */
    public function createOrder(User $user): Order
    {
        return $user->orders()->create();
    }

    /**
     * @param Order $order
     * @param ProductInterface ...$products
     *
     * @return bool
     */
    public function addProducts(Order $order, ProductInterface ...$products): bool
    {
        foreach ($products as $product) {
            $orderProduct = $order->products()->make([
                'initial_price' => $product->priceTag()->initialPrice(),
                'amount' => $product->priceTag()->price(),
            ]);
            $orderProduct->orderable()->associate($product);
            $orderProduct->save();
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
        //TODO что здесь происходит?

        // products
        $initialOrderAmount = $order->products->sum('amount');

        // promocodes
        $appliedPromocodesAmount = 0;
        foreach ($order->promocodes as $orderPromocode) {
            $appliedPromocodesAmount += $orderPromocode->promocode->promoable->priceTag()->price();
        }

        // todo ? book discounts

        return max(($initialOrderAmount - $appliedPromocodesAmount), 0);
    }

    /**
     * @param Order $order
     *
     * @return int
     */
    public function calculateAuthorsOrderRewardFromEdition(Order $order): int
    {
        $orderAmount = $this->calculateAmount($order);
        $awardsAmount = $order->awards->sum('amount');
        $depositAmount = $order->deposits->sum('amount');
        $donationsAmount = $order->donations->sum('amount');

        return max(($orderAmount - $awardsAmount - $depositAmount - $donationsAmount), 0);
    }

    /**
     * @param Order $order
     *
     * @return int
     */
    public function calculateAuthorsOrderRewardFromSupport(Order $order): int
    {
        $donationsAmount = $order->donations->sum('amount');

        return max(($donationsAmount), 0);
    }

    /**
     * @param Order $order
     *
     * @return int
     */
    public function calculateAuthorsOrderSupport(Order $order): int
    {
        $supportAmount = $order->donations->sum('amount');

        return max($supportAmount, 0);
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

        // заказ оплачен
        $this->updateOrderstatus($order, OrderStatusEnum::PAID);

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
        return DB::transaction(function () use ($order, $code) {
            // get promocode
            $promocode = Promocode::query()->code($code)->notActivated()->first();

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
     * @param Book|null $book
     *
     * @return void
     */
    public function applyAwards(Order $order, mixed $awards, ?Book $book = null): void
    {
        $order->awards()->get()->each->delete();

        foreach ($awards as $award) {
            $orderProduct = $order->products()->make([
                'book_id' => $book?->id,
                'initial_price' => $award->price,
                'amount' => $award->price,
            ]);
            $orderProduct->orderable()->associate($award);
            $orderProduct->save();
        }
    }

    /**
     * @param Order $order
     * @param int $donateAmount
     * @param Profile|null $profile
     *
     * @return void
     */
    public function applyAuthorSupport(Order $order, int $donateAmount, Profile $profile = null): void
    {
        if (is_null($profile)) {
            $order->donations()->get()->each->delete();

        } else {
            $appliedDonations = $order->donations()->get();
            $appliedDonations->each(function ($appliedDonation) use ($profile) {
                $appliedDonation->whereHasMorph('orderable', [Donation::class], function ($query) use ($profile) {
                    $query->where('profile_id', $profile->id);
                })->delete();
            });
        }

        if ($donateAmount > 0) {
            $donation = Donation::create([
                'amount' => $donateAmount,
                'profile_id' => $profile?->id,
            ]);

            $orderProduct = $order->products()->make(['amount' => $donateAmount]);
            $orderProduct->orderable()->associate($donation);
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
        $book = $order->editions()
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

            $orderProduct = $order->products()->make(['amount' => $depositAmount]);
            $orderProduct->orderable()->associate($deposit);
            $orderProduct->save();
        }
    }

    /**
     * @param Order $order
     *
     * @return void
     * @throws ApplicationException
     */
    private function giveCustomerProducts(Order $order): void
    {
        $user = $order->user;

        foreach ($order->products as $orderProduct) {
            $product = $orderProduct->orderable;

            if ($this->isProductOwnable($product)) {
                $newUserOwning = $user->ownedBooks()->make();
                $newUserOwning->ownable()->associate($product);
                $newUserOwning->save();

                $product->status === BookStatus::COMPLETE ?
                    $this->operationHistoryService->addReceivingPurchase($order, $orderProduct)
                    :
                    $this->operationHistoryService->addReceivingSubscription($order, $orderProduct);
            }
        }
    }

    /**
     * @param Order $order
     *
     * @return void
     * @throws ApplicationException
     */
    private function addAwardsToEditions(Order $order): void
    {
        foreach ($order->awards as $orderAwardProduct) {
            $award = $orderAwardProduct->orderable;

            if ($orderAwardProduct->book_id) {
                $awardBook = AwardBook::create([
                    'user_id' => $order->user->id,
                    'award_id' => $award->id,
                    'book_id' => $orderAwardProduct->book_id,
                ]);

                $this->operationHistoryService->addMakingAuthorReward($order, $awardBook);
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
        $byEdition = $this->calculateAuthorsOrderRewardFromEdition($order);
        $bySupport = $this->calculateAuthorsOrderRewardFromSupport($order);

        if ($byEdition <= 0 && $bySupport <= 0) {
            return;
        }

        $profiles = $this->resolveOrderRewardReceivers($order);

        if ($profiles->isEmpty()) {
            throw new Exception("Unable to resolve Author(s) for order #{$order->id}");
        }


        /**
         * Разделить гонорар с продажи книги с учетом процентов
         */
        if ($byEdition) {
            $book = $order->editions()
                ->first()
                ?->orderable
                ?->book;

            $book->profiles->each(function ($profile) use ($byEdition) {
                $authorRewardPartRounded = intdiv(($byEdition * $profile->pivot->percent), 100);
                $profile->user->proxyWallet()->deposit($authorRewardPartRounded);
            });
        } /**
         * Разделить вознаграждение поровну
         */
        else {
            $authorsRewardPartRounded = $this->getRewardPartRounded($bySupport, $profiles->count());

            $profiles->each(function ($profile) use ($order, $authorsRewardPartRounded) {
                $profile->user->proxyWallet()->deposit($authorsRewardPartRounded);

                $this->operationHistoryService->addMakingAuthorSupport($order, $profile, $authorsRewardPartRounded);
                $this->operationHistoryService->addReceivingAuthorSupport($order, $profile, $authorsRewardPartRounded);
            });
        }
    }

    /**
     * Разделение вознаграждения - между аккаунтами (не профилями)
     *
     * @param Order $order
     *
     * @return Collection
     */
    private function resolveOrderRewardReceivers(Order $order): Collection
    {

        /**
         * Get author of the Book
         */
        $book = $order->editions()
            ->first()
            ?->orderable
            ?->book;

        if (!is_null($book)) {
            return Collection::make($book->profiles);
        }

        /**
         * Get target profiles from Donations (Author Support)
         */
        return Collection::make($order->donations->map->orderable->map->profile->filter()); // на тестовом есть с profile_id = null
    }


    /**
     * @param Order $order
     *
     * @return void
     * @throws ApplicationException
     */
    private function transferDepositToUserBalance(Order $order): void
    {
        $depositAmount = $order->deposits->sum('amount');

        if ($depositAmount > 0) {
            $order->user->proxyWallet()->deposit($depositAmount);

            $this->operationHistoryService->addBalanceDeposit($order);
        }
    }

    /**
     * @throws Exception
     */
    public function payFromDeposit(Order $order): bool
    {
        // order amount
        $orderAmount = $this->calculateAmount($order);

        // check user balance
        if ((int)$order->user->proxyWallet()->balance < $orderAmount) {
            throw new Exception('Недостаточно средств на балансе');
        }

        return DB::transaction(function () use ($order, $orderAmount) {
            // approve order
            $this->approveOrder($order);

            // withdraw balance
            $order->user->proxyWallet()->withdraw($orderAmount);

            return true;
        });
    }

    /**
     * @param int $amount
     * @param int $partsCount
     *
     * @return int
     */
    public function getRewardPartRounded(int $amount, int $partsCount): int
    {
        return intdiv($amount, $partsCount);
    }
}
