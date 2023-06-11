<?php
declare(strict_types=1);

namespace Books\Orders\Classes\Contracts;

interface OrderReceiptService
{
    public function getReceiptData(): array;
}
