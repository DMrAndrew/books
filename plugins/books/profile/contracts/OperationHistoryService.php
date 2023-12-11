<?php

namespace Books\Profile\Contracts;

use Books\Book\Models\AwardBook;
use Books\Orders\Models\Order;
use Books\Orders\Models\OrderProduct;
use Books\Profile\Models\Profile;
use RainLab\User\Models\User;

interface OperationHistoryService
{
    /** Пополнение баланса */
    public function addBalanceDeposit(Order $order): void;

    /** Получение сертификата анонимно */
    public function addReceivingCertificateAnonymous(User $user, int $amount): void;

    /** Покупка книги (оплата) */
    public function addReceivingPurchase(Order $order, OrderProduct $orderProduct): void;

    /** Покупка подписки на книгу(оплата) */
    public function addReceivingSubscription(Order $order, OrderProduct $orderProduct): void;

    /** Поддержка автора (оплата) */
    public function addMakingAuthorSupport(Order $order, Profile $profile, int $donationAmount): void;

    /** Поддержка автора (получение) */
    public function addReceivingAuthorSupport(Order $order, Profile $profile, int $donationAmount): void;

    /** Получение сертификата не анонимно */
    public function addReceivingCertificatePublic(Order $order): void;

    /** Покупка награды (оплата) */
    public function addMakingAuthorReward(Order $order, AwardBook $awardBook): void;

    /** Вывод средств */
    public function addWithdrawal(User $user, int $withdrawAmount): void;

    /** Корректировка баланса */
    public function addBalanceCorrection(User $user, int $correctionAmount): void;
}
