<?php namespace Books\Book\Console;

use Books\Book\Classes\PromocodeGenerationLimiter;
use Books\Book\Models\Promocode;
use Books\Profile\Models\Profile;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * DeleteNotActivatedFreePromocodes Command
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class DeleteNotActivatedFreePromocodes extends Command
{
    /**
     * @var string signature for the console command.
     */
    protected $signature = 'book:promocodes:delete_free_promocodes_not_activated';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Удаление неиспользованных промокодов, сгенерированных в безлимитный период, по истечении безлимитного периода';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $this->output->writeln("Удаление неиспользованных промокодов");

        /**
         * Безлимитный период заканчивается - дата
         */
        $unlimitedPeriodExpiredAt = Carbon::now()
            ->subMonths(PromocodeGenerationLimiter::UNLIMITED_GENERATION_FREE_MONTHS)
            ->endOfDay();

        /**
         * Промокоды, которые сгенерированы в первые N месяцев регистрации профиля
         */
        $expiredFreePromocodes = Promocode
            ::with('profile')

            /**
             * Которые еще не использовались. Использованные не удаляем
             */
            ->notActivated()

            /**
             * Созданы в безлимитный период
             */
            ->where('created_at', '<', $unlimitedPeriodExpiredAt)

            /**
             * Профиль создан тоже в безлимитный период. Иначе профиль может быть давно существующим
             */
            ->whereHas('profile', function ($profile) use ($unlimitedPeriodExpiredAt) {
                $profile->where('created_at', '<', $unlimitedPeriodExpiredAt);
            })
            ->get();

        $expiredPromocodesCount = $expiredFreePromocodes->count();

        $expiredFreePromocodes->each(function ($promocode) {
            $promocode->delete();
        });

        $this->output->writeln("Удалено {$expiredPromocodesCount} промокодов");

        return;
    }
}
