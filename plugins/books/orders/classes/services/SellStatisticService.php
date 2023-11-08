<?php
declare(strict_types=1);

namespace Books\Orders\Classes\Services;

use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\SellStatisticSellTypeEnum;
use Books\Book\Models\SellStatistics;
use Books\Orders\Classes\Contracts\OrderService as OrderServiceContract;
use Books\Orders\Classes\Contracts\SellStatisticService as SellStatisticServiceContract;
use Books\Orders\Models\Order;
use Carbon\Carbon;

class SellStatisticService implements SellStatisticServiceContract
{
    private OrderServiceContract $orderService;

    public function __construct(OrderServiceContract $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function addSellsFromOrder(Order $order): bool
    {
        $editionsAmount = $this->orderService->calculateAuthorsOrderRewardFromEdition($order);
        if ($editionsAmount <= 0) {
            return true;
        }

        $edition = $order->editions()
            ->first()
            ?->orderable;

        $book = $edition->book;

        $rewardTaxedCoefficient = $this->orderService->getAuthorRewardCoefficient();

        /**
         * Статистику записываем каждому соавтору
         */
        $book->authors->each(function ($author) use (
                $edition,
                $editionsAmount,
                $book,
                $order,
                $rewardTaxedCoefficient
            ) {

            $profile = $author->profile;

            $authorRewardPartRounded = intdiv(($editionsAmount * $author->percent), 100);
            $authorRewardPartTaxed = intval($rewardTaxedCoefficient * $authorRewardPartRounded);

            /**
             * Сохранить статистику продажи
             */
            $sellType = $edition->status === BookStatus::COMPLETE
                ? SellStatisticSellTypeEnum::SELL
                : SellStatisticSellTypeEnum::SUBSCRIBE;

            $price = intdiv(($order->editions->sum('amount') * $author->percent), 100);

            SellStatistics::create([
                'profile_id' => $profile->id,
                'edition_id' => $edition->id,
                'edition_type' => $edition->type->value,
                'sell_at' => Carbon::now(),
                'edition_status' => $edition->status->value,
                'sell_type' => $sellType,
                'price' => $price,
                'reward_rate' => intval($rewardTaxedCoefficient * 100),
                'reward_value' => $authorRewardPartTaxed,
                'tax_rate' => intval(100 - ($rewardTaxedCoefficient * 100)),
                'tax_value' => $authorRewardPartRounded - $authorRewardPartTaxed,
            ]);
        });

        return true;
    }
}
