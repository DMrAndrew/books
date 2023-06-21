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

        $book = $order->editions()
            ->first()
            ?->orderable
            ?->book;

        $rewardTaxedCoefficient = $this->orderService->getAuthorRewardCoefficient();

        $book->profiles->each(function ($profile) use ($editionsAmount, $book, $order, $rewardTaxedCoefficient) {
            $authorRewardPartRounded = intdiv(($editionsAmount * $profile->pivot->percent), 100);
            $authorRewardPartTaxed = intval($rewardTaxedCoefficient * $authorRewardPartRounded);

            /**
             * Сохранить статистику продажи
             */
            $edition = $book->editions()->first();
            $sellType = $edition->status === BookStatus::COMPLETE
                ? SellStatisticSellTypeEnum::SELL
                : SellStatisticSellTypeEnum::SUBSCRIBE;

            SellStatistics::create([
                'profile_id' => $profile->id,
                'edition_id' => $edition->id,
                'edition_type' => $edition->type->value,
                'sell_at' => Carbon::now(),
                'edition_status' => $edition->status->value,
                'sell_type' => $sellType,
                'price' => $order->editions->sum('amount'),
                'reward_rate' => intval($rewardTaxedCoefficient * 100),
                'reward_value' => $authorRewardPartTaxed,
                'tax_rate' => intval(100 - ($rewardTaxedCoefficient * 100)),
                'tax_value' => $authorRewardPartRounded - $authorRewardPartTaxed,
            ]);
        });

        return true;
    }
}
