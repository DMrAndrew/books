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

    /**
     * @param string $code
     *
     * @return void
     */
    public function saveReferralCookie(string $code): void
    {
        $cookie = Cookie::forever(self::REFERRAL_COOKIE_NAME, $code);
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
     * @param User $referral
     *
     * @return Referrals
     */
    public function addReferral(Referrer $refferer, User $referral): Referrals
    {
        return Referrals::create([
            'user_id' => $referral->id,
            'referrer_id' => $refferer->id,
        ]);
    }
}
