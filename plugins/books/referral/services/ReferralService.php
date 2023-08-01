<?php
declare(strict_types=1);

namespace Books\Referral\Services;

use App;
use Books\Orders\Classes\Contracts\OrderService as OrderServiceContract;
use Books\Orders\Models\Order;
use Books\Referral\Contracts\ReferralServiceContract;
use Books\Referral\Models\Referrals;
use Books\Referral\Models\ReferralStatistics;
use Books\Referral\Models\Referrer;
use Carbon\Carbon;
use Cookie;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

class ReferralService implements ReferralServiceContract
{
    const REFERRAL_COOKIE_NAME = 'referrer_partner';
    const REFERRAL_REWARD_PERCENT = 3;

    /**
     * @param string $code
     *
     * @return void
     */
    public function saveReferralCookie(string $code): void
    {
        $cookie = Cookie::make(self::REFERRAL_COOKIE_NAME, $code, minutes: 60 * 24 * Referrals::COOKIE_LIVE_TIME_DAYS);
        Cookie::queue($cookie);
    }

    /**
     * @return string|null
     */
    public function getReferralCookie(): ?string
    {
        return Cookie::get(self::REFERRAL_COOKIE_NAME);
    }

    /**
     * @return void
     */
    public function forgetReferralCookie(): void
    {
        $cookie = Cookie::forget(self::REFERRAL_COOKIE_NAME);
        Cookie::queue($cookie);
    }

    /**
     * @return void
     */
    public function processReferralCookie(): void
    {
        $referralCookie = $this->getReferralCookie();
        if ($referralCookie) {
            $referrer = Referrer::where('code', $referralCookie)->first();
            $user = Auth::getUser();

            if ($referrer && $user) {
                if ($referrer->user_id !== $user->id) {
                    $this->addReferral($referrer, Auth::getUser());
                }
            }
        }
    }

    /**
     * @param Referrer $referrer
     * @param User $user
     *
     * @return Referrals
     */
    public function addReferral(Referrer $referrer, User $user): Referrals
    {
        /**
         * Если пользователь переходит по ссылке другого партнера,
         * то он прикрепляется к нему и счетчик в 2 недели включается заново
         */
        $referral = Referrals::where('user_id', $user->id)->first();

        if ($referral) {
            $referral->update([
                'referrer_id' => $referrer->id,
                'valid_till' => now()->addDays(Referrals::REFERRAL_LIVE_TIME_DAYS),
            ]);
        } else {
            $referral = Referrals::create([
                'user_id' => $user->id,
                'referrer_id' => $referrer->id,
                'valid_till' => now()->addDays(Referrals::REFERRAL_LIVE_TIME_DAYS),
            ]);
        }

        return $referral;
    }

    /**
     * @param User $user
     *
     * @return Referrer|null
     */
    public function getActiveReferrerOfCustomer(User $user): ?Referrer
    {
        return Referrals
            ::where('user_id', $user->id)
            ->first()
            ?->referrer;
    }

    /**
     * @return int
     */
    public function getRewardPercent(): int
    {
        return self::REFERRAL_REWARD_PERCENT;
    }

    /**
     * @param Order $order
     * @param Referrer $referrer
     *
     * @return void
     */
    public function saveReferralSellStatistic(Order $order, Referrer $referrer): void
    {
        $orderService = app(OrderServiceContract::class);
        $rewardByEdition = $orderService->calculateAuthorsOrderRewardFromEdition($order);

        $rewardPercent = $this->getRewardPercent();

        if ($rewardByEdition) {
            $referrerRewardPartRounded = intdiv(($rewardByEdition * $rewardPercent), 100);
        } else {
            $referrerRewardPartRounded = 0;
        }

        $data = [
            'user_id' => $referrer->user->id,
            'referrer_id' => $referrer->id,
            'order_id' => $order->id,
            'sell_at' => now(),
            'price' => $rewardByEdition,
            'reward_rate' => $rewardPercent,
            'reward_value' => $referrerRewardPartRounded,
        ];

        ReferralStatistics::create($data);
    }

    /**
     * @param Order $order
     *
     * @return void
     */
    public function rewardReferrer(Order $order): void
    {
        $referrer = $this->getActiveReferrerOfCustomer($order->user);
        if ($referrer) {
            $orderService = app(OrderServiceContract::class);
            $rewardByEdition = $orderService->calculateAuthorsOrderRewardFromEdition($order);
            if ($rewardByEdition) {
                $rewardPercent = $this->getRewardPercent();
                $referrerRewardPartRounded = intdiv(($rewardByEdition * $rewardPercent), 100);
                $referrer->user->proxyWallet()->deposit($referrerRewardPartRounded, ['Реферальная программа' => "Заказ №{$order->id}"]);
            }
        }
    }
}
