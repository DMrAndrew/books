<?php
declare(strict_types=1);

namespace Books\Payment\Classes;

use Omnipay\Omnipay;

/**
 * YooKassa - https://yookassa.ru/docs
 *
 * тестовые карты с разным статусом ответа:
 * https://yookassa.ru/developers/payment-acceptance/testing-and-going-live/testing?ysclid=lgwcau6d1r312270278#test-bank-card-success
 * Успешная оплата: 5555555555554477
 */
class PaymentService
{
    public $gateway;

    public function __construct()
    {
        $this->gateway = Omnipay::create('YooKassa');
        $this->gateway->setShopId(env('YOOKASSA_SHOP_ID'));
        $this->gateway->setSecret(env('YOOKASSA_SECRET'));
    }
}
