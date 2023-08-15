<?php namespace Books\Referral\Updates;

use App;
use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Models\Order;
use Books\Referral\Contracts\ReferralServiceContract;
use Books\Referral\Models\ReferralStatistics;
use Books\Referral\Models\Referrer;
use Carbon\Carbon;
use October\Rain\Database\Updates\Seeder;
use RainLab\User\Models\User;

/**
 * SeedStatistic Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class seed_sell_statistics extends Seeder
{
    const SEED_RECORDS = 300;

    public function run()
    {
        /**
         * Seeding only in develop environment
         */
        if (App::environment() === 'production') {
            echo 'Skip seeding `books_referral_statistics` table in production.';

            return true;
        }

        /**
         * Get referrer
         */
        $referrer = Referrer::first();
        if ($referrer == null) {
            $user = User::first();

            if (!$user) {
                echo 'No Users in database found. Skip seeding `books_referral_statistics` table.';

                return true;
            }

            $referrer = $user->referrer()->create([
                'target_link' => url('/some-fake-link'),
            ]);
        } else {
            $user = $referrer->user;
        }

        /**
         * Fake order related
         */
        $order = Order::whereStatus(OrderStatusEnum::PAID)->first();

        /**
         * Seeding referrer statistics
         */
        for ($i = 0; $i <= self::SEED_RECORDS; $i++) {
            $referralService = app(ReferralServiceContract::class);
            $rewardRate = $referralService->getRewardPercent();

            $price = rand(50, 1990);
            $rewardValue = intval($price * $rewardRate / 100);

            $data = [
                'user_id' => $user->id,
                'referrer_id' => $referrer->id,
                'order_id' => $order->id,
                'sell_at' => Carbon::today()->subDays(rand(0, 365))->subHours(rand(1, 23))->subMinutes(rand(1, 55)),
                'price' => $price,
                'reward_rate' => $rewardRate,
                'reward_value' => $rewardValue,
            ];

            ReferralStatistics::create($data);
        }

        echo 'Seeding completed. User with seeded statistics: id ' . $user->id . PHP_EOL;

        return true;
    }
}
