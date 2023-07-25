<?php
declare(strict_types=1);

namespace Books\Referral\Contracts;

use Books\Orders\Models\Order;
use Books\Referral\Models\Referrals;
use Books\Referral\Models\Referrer;
use RainLab\User\Models\User;

interface ReferralServiceContract
{
    public function saveReferralCookie(string $code): void;

    public function getReferralCookie(): ?string;

    public function forgetReferralCookie(): void;

    public function processReferralCookie(): void;

    public function addReferral(Referrer $referrer, User $user): Referrals;

    public function getActiveReferrerOfCustomer(User $user): ?Referrer;

    public function getRewardPercent(): int;

    public function saveReferralSellStatistic(Order $order, Referrer $referrer): void;

    public function rewardReferrer(Order $order): void;
}
