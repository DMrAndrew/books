<?php
declare(strict_types=1);

namespace Books\Payment\Contracts;

use Books\Orders\Models\Order;
use Illuminate\Http\Request;

interface PaymentService
{
    public function charge(Order $order);
    public function webhook(Request $request);
    public function success(Request $request);
    public function error(Request $request);
}
