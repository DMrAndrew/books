<?php
declare(strict_types=1);

namespace Books\Referral\Services;

use Books\Referral\Contracts\ReferralServiceContract;
use Books\Referral\Models\Referrals;
use Books\Referral\Models\Referrer;
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
     * @param Referrer $refferer
     * @param User $user
     *
     * @return Referrals
     */
    public function addReferral(Referrer $refferer, User $user): Referrals
    {
        /**
         * Если пользователь переходит по ссылке другого партнера,
         * то он прикрепляется к нему и счетчик в 2 недели включается дзаново
         */
        $referral = Referrals::where('user_id', $user->id)->first();

        if ($referral) {
            $referral->update([
                'referrer_id' => $refferer->id,
                'valid_till' => now()->addDays(Referrals::REFERRAL_LIVE_TIME_DAYS),
            ]);
        } else {
            $referral = Referrals::create([
                'user_id' => $user->id,
                'referrer_id' => $refferer->id,
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
        return Referrer
            ::whereHas('referrals', function ($query) use ($user) {
                $query
                    ->active()
                    ->where('user_id', $user->id);
            })
            ->first();
    }

    /**
     * @return int
     */
    public function getRewardPercent(): int
    {
        return self::REFERRAL_REWARD_PERCENT;
    }

    public function saveReferralSellStatistic(): void
    {
        // TODO: Implement saveReferralSellStatistic() method.
    }
}
