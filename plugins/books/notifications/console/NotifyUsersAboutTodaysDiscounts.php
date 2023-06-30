<?php namespace Books\Notifications\Console;

use Books\Book\Models\Discount;
use Books\Notifications\Jobs\DiscountNotificationJob;
use Illuminate\Console\Command;

/**
 * NotifyUsersAboutTodaysDiscounts Command
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class NotifyUsersAboutTodaysDiscounts extends Command
{
    /**
     * @var string signature for the console command.
     */
    protected $signature = 'book:discounts:notify_user_about_todays_discounts';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Уведомление пользователей о новых скидках';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $this->output->writeln("Уведомление пользователей о новых скидках");

        /**
         * Сегодняшние скидки
         */
        $today = today()->startOfDay();
        $discounts = Discount::whereDate('active_at', '=', $today)->get();

        if ($discounts->count() == 0) {
            $this->info("Скидок на сегодняшний день не найдено");

            return;
        }

        $discounts->each(function ($discount) {
            DiscountNotificationJob::dispatch($discount);
        });

        $this->info("Отправлены уведомления по {$discounts->count()} скидкам");

        return;
    }
}
