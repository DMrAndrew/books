<?php
declare(strict_types=1);

namespace Books\Orders\Classes\Services;

use Books\Orders\Classes\Contracts\OrderService as OrderServiceContract;
use Books\Orders\Models\Order;
use RainLab\User\Models\User;

class OrderService implements OrderServiceContract
{
    public function createOrder(User $user, array $attributes): Order
    {
        $order = new Order();
        $order->user = $user;
        $order->save();

        return $order;
    }

    public function getPrice(): int
    {
        // TODO: Implement getPrice() method.
    }

    public function calculateAmount(): int
    {
        // TODO: Implement calculateAmount() method.
    }

    public function payOrderByTransaction(Order $order): bool
    {
        // TODO: Implement payOrderByTransaction() method.
    }

    public function payOrderFromBalance(Order $order): bool
    {
        // TODO: Implement payOrderFromBalance() method.
    }

    public function approveOrder(Order $order): bool
    {
        // TODO: Implement approveOrder() method.
    }

    public function cancelOrder(Order $order): bool
    {
        // TODO: Implement cancelOrder() method.
    }

    public function applyPromocode(Order $order): void
    {
        // TODO: Implement applyPromocode() method.
    }

    public function applyDiscount(Order $order): void
    {
        // TODO: Implement applyDiscount() method.
    }

    public function applyReward(Order $order): void
    {
        // TODO: Implement applyReward() method.
    }

    public function applyAuthorSupport(Order $order): void
    {
        // TODO: Implement applyAuthorSupport() method.
    }

    public function updateAuthorsBalance(Order $order): void
    {
        // TODO: Implement updateAuthorsBalance() method.
    }
}
