<?php

namespace Books\Orders\Classes\Contracts;

use Books\Orders\Models\Order;

interface SellStatisticService
{
    public function addSellsFromOrder(Order $order): bool;
}
