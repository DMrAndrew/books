<?php namespace Books\Orders\Console;

use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Models\Order;
use Illuminate\Console\Command;

/**
 * DeleteNotActivatedFreePromocodes Command
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class DiscardOldOrders extends Command
{
    /**
     * @var string signature for the console command.
     */
    protected $signature = 'book:orders:discard_old_orders';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Выставляет старым заказам статус Отменен';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $this->output->writeln("Обработка заброшенных заказов");

        $abandonedOrdersCount = 0;

        /**
         * Старыми считаем заказы от 24часов
         */
        $expiredAt = now()->subHours(24);

        $abandonedOrders = Order
            ::where('updated_at', '<', $expiredAt)
            ->whereNotIn('status', [OrderStatusEnum::PAID, OrderStatusEnum::CANCELED])
            ->get();

        $abandonedOrders->each(function ($order) use (&$abandonedOrdersCount) {
            $order->status = OrderStatusEnum::CANCELED->value;
            $order->save();

            $abandonedOrdersCount++;
        });

        $this->output->writeln("Отменено {$abandonedOrdersCount} заказов");

        return;
    }
}
