<?php
declare(strict_types=1);

namespace Books\Profile\Classes\Enums;

enum OperationType: int
{
    case DepositOnBalance = 1;
    case TransferOnBalance = 2;
    case Sell = 3;
    case Subscribed = 4;
    case GetReward = 5; // получение награды

    public function label(): string
    {
        return match ($this) {
            self::DepositOnBalance => 'Пополнение баланса', // пополнение баланса => Баланс пополнен на 500 руб
            self::TransferOnBalance => 'Зачисление на баланс',// Вы получили 500 руб от Виктора
            self::Sell => 'Продажа произведения', // продажа <книги> => Куплена книга "Воплощение мечты" за 300 руб
            self::Subscribed => 'Оформление подписки',// Оформлена подписка на книгу «Воплощение мечты» за 300 ₽
            self::GetReward => 'Получение награды',
        };
    }
}

