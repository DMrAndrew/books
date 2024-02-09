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
use Books\Orders\Models\OrderPromocode;
use Books\Profile\Models\Profile;
use Books\Profile\Contracts\OperationHistoryService as OperationHistoryServiceContract;
use Books\Referral\Contracts\ReferralServiceContract;
use Carbon\Carbon;
use Db;
use Exception;
use October\Rain\Support\Collection;
use October\Rain\Database\Model;
use RainLab\User\Models\User;
use Books\Orders\Classes\Contracts\SellStatisticService as SellStatisticServiceContract;

class OrderService implements OrderServiceContract
{
    private OperationHistoryServiceContract $operationHistoryService;

    public function __construct()
    {
        $this->operationHistoryService = app(OperationHistoryServiceContract::class);
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
        $orderTotalAmount = $this->calculateAmount($order);

        $awardsPartAmount = $order->awards->sum('amount');
        $depositsPartAmount = $order->deposits->sum('amount');
        $donationsPartAmount = $order->donations->sum('amount');

        return max(($orderTotalAmount - $awardsPartAmount - $depositsPartAmount - $donationsPartAmount), 0);
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

        // бонус по реферальной программе
        $this->rewardReferrer($order);

        // заказ оплачен
        $this->updateOrderstatus($order, OrderStatusEnum::PAID);

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
            $promocode = Promocode::query()->code($code)->alive()->first();

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
        /** @var Model $promocodeProduct */
        $promocodeProduct = $promocode->promoable;

        return $order->products
            ->where('orderable_type', $promocodeProduct::class)
            ->where('orderable_id', $promocodeProduct->id)
            ->count() > 0;
    }

    /**
     * @param Order $order
     * @param Promocode $promocode
     *
     * @return void
     */
    public function activatePromocode(Order $order, Promocode $promocode): void
    {
        $orderPromocode = OrderPromocode::firstOrCreate([
            'order_id' => $order->id,
            'promocode_id' => $promocode->id,
        ]);

        if ($orderPromocode->wasRecentlyCreated) {
            $promocode->update([
                'is_activated' => true,
                'activated_at' => Carbon::now(),
                'user_id' => $order->user_id,
                'used_by_profile_id' => $order->user->profile->id
            ]);
        }
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
        // book from awards, donations
        $bookId = $order->book_id;
        if ($bookId) {
            return url('book-card', ['book_id' => $bookId]);
        }

        // from edition
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

    /**
     * @param Order $order
     * @param int $depositAmount
     *
     * @return void
     */
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

            $book->profiles->each(function ($profile) use ($byEdition, $book, $order) {
                $rewardTaxedCoefficient = $this->getAuthorRewardCoefficient($profile->user->birthday?->isBirthday());
                $authorRewardPartRounded = intdiv(($byEdition * $profile->pivot->percent), 100);
                $authorRewardPartTaxed = intval($rewardTaxedCoefficient * $authorRewardPartRounded);
                $profile->user->proxyWallet()->deposit($authorRewardPartTaxed);
            });

            /**
             * Сохранить статистику продажи
             */
            $sellStatisticService = app(SellStatisticServiceContract::class);
            $sellStatisticService->addSellsFromOrder($order);
        }

        /**
         * Разделить вознаграждение поровну
         */
        if ($bySupport) {
            $authorsRewardPartRounded = $this->getRewardPartRounded($bySupport, $profiles->count());

            $profiles->each(function ($profile) use ($order, $authorsRewardPartRounded) {
                $rewardTaxedCoefficient = $this->getAuthorRewardCoefficient($profile->user->birthday?->isBirthday());
                $authorRewardPartTaxed = intval($rewardTaxedCoefficient * $authorsRewardPartRounded);
                $profile->user->proxyWallet()->deposit($authorRewardPartTaxed);

                /**
                 * Вы наградили автора <вся сумма>
                 */
                $this->operationHistoryService->addMakingAuthorSupport($order, $profile, $authorsRewardPartRounded);
                /**
                 * Вы получили <сумма с учетом комиссии> от
                 */
                $this->operationHistoryService->addReceivingAuthorSupport($order, $profile, $authorRewardPartTaxed);
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

        if ((int)$order->user->proxyWallet()->balance < $orderAmount) {
            throw new Exception('Недостаточно средств на балансе');
        }

        $this->approveOrder($order);

        // withdraw balance
        $order->user->proxyWallet()->withdraw($orderAmount);

        return true;
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

    /**
     * @return float
     */
    public function getAuthorRewardCoefficient($isAuthorBirthday): float
    {
        return $isAuthorBirthday ? 1 : ((int)config('orders.author_reward_percentage') / 100);
    }

    /**
     * @param Order $order
     *
     * @return void
     */
    private function rewardReferrer(Order $order): void
    {
        $referralService = app(ReferralServiceContract::class);

        $referrer = $referralService->getActiveReferrerOfCustomer($order->user);
        if ($referrer) {
            $referralService->saveReferralSellStatistic($order, $referrer);
            $referralService->rewardReferrer($order);
        }
    }
}
