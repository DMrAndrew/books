<?php namespace Books\Payment\Components;

use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Classes\Services\OrderService;
use Books\Orders\Models\Order as OrderModel;
use Books\Payment\Models\Payment as PaymentModel;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Payment Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Payment extends ComponentBase
{
    private ?OrderModel $order;
    private OrderService $orderService;
    private ?User $user;

    public function componentDetails()
    {
        return [
            'name' => 'Payment Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->user = Auth::getUser();
        if (!$this->user) {
            abort(404);
        }

        $this->orderService = app(OrderService::class);
        $this->order = $this->getOrder($this->param('order_id'));

        $this->prepareVals();
    }

    public function prepareVals()
    {
        $this->page['order_id'] = $this->param('order_id');
        $this->page['paymentData'] = $this->getPaymentData();
    }

    /**
     * @param int $orderId
     *
     * @return OrderModel
     */
    private function getOrder(int $orderId): OrderModel
    {
        return OrderModel::findOrFail($orderId);
    }

    /**
     * @param OrderModel $order
     *
     * @return PaymentModel
     */
    private function getPayment(OrderModel $order): PaymentModel
    {
        return PaymentModel::firstOrCreate(
            [
                'order_id' => $order->id,
            ],[
                'payer_id' => $order->user->id,
                'payer_email' => $order->user->email,
                'amount' => $this->orderService->calculateAmount($order),
                'currency' => PaymentModel::CURRENCY,
                'payment_status' => 'created',
            ]
        );
    }

    private function getPaymentData(): array
    {
        //$order = $this->getOrder($this->param('order_id'));
        $this->orderService->updateOrderstatus($this->order, OrderStatusEnum::PENDING);

        // create payment
        $payment = $this->getPayment($this->order);

        return [
            'publicId' => config('cloudpayments.publicId'), // Required
            'amount' => $payment->amount, // Required
            'Currency' => $payment->currency, // Required

            'Name' => "Заказ №{$this->order->id}", // Required
            'ipAddress' => getHostByName(getHostName()), // Required

            //'CardCryptogramPacket' => null, //$CardCryptogramPacket, // Required
            'invoiceId' => $this->order->id,
            'description' => "Заказ №{$this->order->id}",
            'accountId' => $this->order->user->id,
            'email' => $this->order->user->email,
            'data' => json_encode([
                'userId' => $this->order->user->id,
                'userName' => $this->order->user->username,
                'email' => $this->order->user->email,
            ]),
        ];
    }
}
