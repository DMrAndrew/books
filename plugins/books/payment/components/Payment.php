<?php namespace Books\Payment\Components;

use Books\Orders\Classes\Services\OrderService;
use Books\Orders\Models\Order as OrderModel;
use Books\Payment\Models\Payment as PaymentModel;
use Books\Payment\Classes\PaymentService;
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
    private ?Order $order;
    private OrderService $orderService;
    private PaymentService $paymentService;

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
        $this->orderService = app(OrderService::class);
        $this->paymentService = app(PaymentService::class);

        $this->user = Auth::getUser();

        $this->order = $this->getOrder($this->param('order_id'));

        //dd('init');
        $this->charge();
    }

    /**
     * Initiate a payment
     *
     * @return string|void|null
     */
    public function charge()
    {
        $order = $this->getOrder($this->param('order_id'));

        try {
            // create payment
            $payment = $this->getPayment($order);

            dd($payment);

            // run yookassa payment
            $response = $this->gateway->purchase([
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'description' => "Заказ №{$order->id}",

                //'status' => 'pending',
                'capture' => false,
                'recipient' => $payment->payer_email,
                'transactionId' => $payment->payment_id,

                'returnUrl' => route('payment.success', ['order' => $order->id]),
                'cancelUrl' => route('payment.error', ['order' => $order->id]),
            ])->send();

            if ($response->isRedirect()) {
                $response->redirect(); // this will automatically forward the customer
            } else {
                // not successful
                return $response->getMessage();
            }
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @return User
     */
    private function getUser(): User
    {
        if (!Auth::check()) {
            $this->controller->run('/404');
        }

        return Auth::getUser();
    }

    /**
     * @param int $orderId
     *
     * @return OrderModel
     */
    private function getOrder(int $orderId): OrderModel
    {
        $order = OrderModel::findOrFail($orderId);

        if ($order->user->id !== $this->getUser()->id) {
            $this->controller->run('/404');
        }

        return $order;
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
        ]);
    }
}
