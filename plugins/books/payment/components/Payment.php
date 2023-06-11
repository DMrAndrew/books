<?php namespace Books\Payment\Components;

use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Classes\Services\OrderReceiptService;
use Books\Orders\Classes\Services\OrderService;
use Books\Orders\Models\Order as OrderModel;
use Books\Payment\Models\Payment as PaymentModel;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;

/**
 * Payment Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Payment extends ComponentBase
{
    private ?OrderModel $order;
    private OrderService $orderService;
    private OrderReceiptService $receiptService;
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
        $this->receiptService = app()->makeWith(OrderReceiptService::class, ['order' => $this->order]);

        $this->prepareVals();
    }

    public function onRun()
    {
        if ($this->isOrderPaid()) {
            $orderSuccessPage = $this->orderService->getOrderSuccessRedirectPage($this->order);

            return Redirect::to($orderSuccessPage);
        }

        $this->orderService->updateOrderstatus($this->order, OrderStatusEnum::PENDING);
    }

    /**
     * @return void
     */
    public function prepareVals()
    {
        $this->page['order'] = $this->order;
        $this->page['paymentData'] = $this->getPaymentData();
        $this->page['receiptDataJson'] = json_encode($this->getPaymentReceiptData());
        $this->page['successUrl'] = $this->orderService->getOrderSuccessRedirectPage($this->order);
        $this->page['errorUrl'] = $this->orderService->getOrderErrorRedirectPage($this->order);
    }

    public function isOrderPaid()
    {
        return $this->order->status === OrderStatusEnum::PAID->value;
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
        $payment = $this->getPayment($this->order);

        return [
            'orderStatus' => $this->order->status,
            'paymentStatus' => $payment->payment_status,

            'publicId' => config('cloudpayments.publicId'), // Required
            'amount' => $payment->amount, // Required
            'Currency' => $payment->currency, // Required

            'Name' => "Заказ №{$this->order->id}", // Required
            'ipAddress' => getHostByName(getHostName()), // Required

            'invoiceId' => $this->order->id,
            'description' => "Заказ №{$this->order->id}",
            'accountId' => $this->order->user->id,
            'email' => $this->order->user->email,
            'data' => [
                'paymentId' => $payment->payment_id,
                'userId' => $this->order->user->id,
                'userName' => $this->order->user->username,
                'email' => $this->order->user->email,
            ],
        ];
    }

    private function getPaymentReceiptData(): array
    {
        return $this->receiptService->getReceiptData();
    }
}
