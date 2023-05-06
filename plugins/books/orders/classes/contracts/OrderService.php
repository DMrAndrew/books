<?php

namespace Books\Orders\Classes\Contracts;

use Books\Orders\Models\Order;
use RainLab\User\Models\User;

interface OrderService
{
    public function getPrice(): int;

    public function calculateAmount(): int;

    public function createOrder(User $user, array $products): Order;

    public function payOrderByTransaction(Order $order): bool;
    public function payOrderFromBalance(Order $order): bool;

    public function approveOrder(Order $order): bool;
    public function cancelOrder(Order $order): bool;

    public function applyPromocode(Order $order): void;
    public function applyDiscount(Order $order): void;
    public function applyAward(Order $order): void;
    public function applyAuthorSupport(Order $order): void;
    public function updateAuthorsBalance(Order $order): void;
}
