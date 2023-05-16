<?php

namespace Books\Orders\Classes\Contracts;

use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Models\Order;
use October\Rain\Database\Collection;
use RainLab\User\Models\User;

interface OrderService
{
    public function createOrder(User $user, array $products): Order;

    public function applyPromocode(Order $order, string $promocode): bool;
    public function applyAwards(Order $order, Collection $awards): void;
    public function applyAuthorSupport(Order $order, int $donate): void;

    public function calculateAmount(Order $order): int;

    public function updateOrderstatus(Order $order, OrderStatusEnum $status): bool;
    public function approveOrder(Order $order): bool;
    public function cancelOrder(Order $order): bool;

    public function getOrderSuccessRedirectPage(Order $order): string;
    public function getOrderErrorRedirectPage(Order $order): string;
}
