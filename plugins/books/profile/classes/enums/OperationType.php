<?php
declare(strict_types=1);

namespace Books\Profile\Classes\Enums;

enum OperationType: int
{
    case DepositOnBalance = 1;
    case TransferOnBalance = 2;
    case Sell = 3;
    case Buy = 4;

    case Subscribed = 5;
    case RewardMake = 6;
    case RewardReceive = 7;
    case Withdraw = 8;

    case SupportMake = 9;
    case SupportReceive = 10;

    public function label(): string
    {
        return match ($this) {
            self::DepositOnBalance => 'Пополнение баланса', // пополнение баланса => Баланс пополнен на 500 руб
            self::TransferOnBalance => 'Зачисление на баланс',// Вы получили 500 руб от Виктора
            self::Sell => 'Продажа произведения', // todo?
            self::Buy => 'Покупка произведения', // Куплена книга "Воплощение мечты" за 300 руб

            self::Subscribed => 'Оформление подписки',// Оформлена подписка на книгу «Воплощение мечты» за 300 ₽
            self::RewardMake => 'Награда', // Вы наградили книгу «Название произведения» на сумму XX ₽
            self::RewardReceive => 'Получение награды', // todo?
            self::Withdraw => 'Вывод средств',

            self::SupportMake => 'Поддержка', // Вы поддержали автора на сумму 300 руб
            self::SupportReceive => 'Получение поддержки', // Вы получили 300 руб от Виктора
        };
    }

    public function bodyTemplate(): string
    {
        return match ($this) {
            self::DepositOnBalance => 'deposit_on_balance',
            self::TransferOnBalance => 'transfer_on_balance',
            self::Sell => 'sell',
            self::Buy => 'buy',

            self::Subscribed => 'subscribed',
            self::RewardMake => 'reward_make',
            self::RewardReceive => 'reward_receive',
            self::Withdraw => 'withdraw',

            self::SupportMake => 'support_make',
            self::SupportReceive => 'support_receive',
        };
    }
}

