<?php namespace Books\Notifications\Console;

use Books\Book\Models\Discount;
use Books\Notifications\Jobs\DiscountNotificationJob;
use Illuminate\Console\Command;

/**
 * NotifyUsersAboutTodayDiscounts Command
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class NotifyUsersAboutTodayDiscounts extends Command
{
    /**
     * @var string signature for the console command.
     */
    protected $signature = 'book:discounts:notify_user_about_today_discounts';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Уведомление пользователей о новых скидках';

    protected string $message = "Отправлены уведомления по %s %s";

    /**
     * handle executes the console command.
     */
    public function handle(): void
    {
        $this->output->writeln("Уведомление пользователей о новых скидках");

        /**
         * Сегодняшние скидки
         */
        $discounts = Discount::query()->active()->get();

        $discounts->each(function ($discount) {
            DiscountNotificationJob::dispatch($discount);
        });

        $count = $discounts->count();
        $this->info(sprintf($this->message, $count, word_form(['скидке', 'скидкам'], $count)));
    }
}
