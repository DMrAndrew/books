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
    case GetReward = 6;
    case Withdraw = 7;
    case Support = 8;

    public function label(): string
    {
        return match ($this) {
            self::DepositOnBalance => 'Пополнение баланса', // пополнение баланса => Баланс пополнен на 500 руб
            self::TransferOnBalance => 'Зачисление на баланс',// Вы получили 500 руб от Виктора
            self::Sell => 'Продажа произведения', // todo?
            self::Buy => 'Покупка произведения', // Куплена книга "Воплощение мечты" за 300 руб
            self::Subscribed => 'Оформление подписки',// Оформлена подписка на книгу «Воплощение мечты» за 300 ₽
            self::GetReward => 'Получение награды', // todo?
            self::Withdraw => 'Вывод средств',
            self::Support => 'Поддержка', // Выполучили 300 руб от Виктора
        };
    }
}

