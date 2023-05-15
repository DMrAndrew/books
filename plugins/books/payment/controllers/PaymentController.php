<?php
declare(strict_types=1);

namespace Books\Payment\Controllers;

use Backend\Classes\Controller;
use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Classes\Services\OrderService;
use Books\Orders\Models\Order;
use Books\Orders\Models\Order as OrderModel;
use Books\Payment\Classes\PaymentService;
use Books\Payment\Models\Payment as PaymentModel;
use Cms\Classes\CmsException;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * YooKassa - https://yookassa.ru/docs
 *
 * тестовые карты с разным статусом ответа:
 * https://yookassa.ru/developers/payment-acceptance/testing-and-going-live/testing?ysclid=lgwcau6d1r312270278#test-bank-card-success
 * Успешная оплата: 5555555555554477
 */
class PaymentController extends Controller
{
    private OrderService $orderService;
    private PaymentService $paymentService;

    /**
     * @throws CmsException
     */
    public function __construct()
    {
        parent::__construct();

        $this->orderService = app(OrderService::class);
        $this->paymentService = app(PaymentService::class);
    }

    public function index()
    {
        return 'payment controller index()';
    }

    /**
     * Initiate a payment
     *
     * @param Request $request
     * @param int $order
     *
     * @return Application|ResponseFactory|Response|string|null
     */
    public function charge(Request $request, int $order): Response|string|Application|ResponseFactory|null
    {
        try {
            $order = $this->getOrder($order);
            $this->orderService->updateOrderstatus($order, OrderStatusEnum::PENDING);

            // create payment
            $payment = $this->getPayment($order);

            // run yookassa payment
            $response = $this->paymentService->gateway->purchase([
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'description' => "Заказ №{$order->id}",

                'capture' => true,
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
            Log::error($e->getMessage());

            return $e->getMessage();
        }

        return response('', 200);
    }

    /**
     * Webhook from payment gateway for updating status
     *
     * @param Request $request
     */
    public function webhook(Request $request)
    {
        try {
            // Once the transaction has been approved, we need to complete it
            $object = $request->object ?? null;

            if ($object) {
                $transactionId = $object['metadata']['transactionId'];
                $paymentStatus = $object['status'];

                // update payment status
                $payment = PaymentModel::where('payment_id', $transactionId)->firstOrFail();
                $payment->update(['payment_status' => $paymentStatus]);

                // update order status
                $order = $payment->order;

                /**
                 * available YooKassa payment statuses - https://yookassa.ru/developers/using-api/webhooks#events-basics
                 */
                $orderStatus = match ($paymentStatus) {
                    'waiting_for_capture' => OrderStatusEnum::PENDING,
                    'succeeded' => OrderStatusEnum::PAID,
                    'canceled' => OrderStatusEnum::CANCELED,
                };
                $this->orderService->updateOrderstatus($order, $orderStatus);

                switch ($paymentStatus) {
                    // ожидает подтверждения
                    case 'waiting_for_capture':
                        $this->orderService->updateOrderstatus($order, OrderStatusEnum::AWAIT_APPROVE);
                        break;

                    // успешно
                    case 'succeeded':
                        if ($order->status != OrderStatusEnum::PAID->value) {
                            $this->orderService->updateOrderstatus($order, OrderStatusEnum::PAID);
                            $isApproved = $this->orderService->approveOrder($order);

                            if (!$isApproved) {
                                throw new Exception("Something went wrong with updating order #{$order->id}");
                            }
                        }
                        break;

                    // отменен
                    case 'canceled':
                        if ($order->status != OrderStatusEnum::CANCELED->value) {
                            $this->orderService->updateOrderstatus($order, OrderStatusEnum::CANCELED);
                            $isCancelled = $this->orderService->cancelOrder($order);

                            if (!$isCancelled) {
                                throw new Exception("Something went wrong with cancelling order #{$order->id}");
                            }
                        }
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());

            /**
             * Yookassa webhook needs response 200
             */
            abort(300, $e->getMessage());
        }
    }

    /**
     * Success page for customer
     *
     * @param Request $request
     * @param int $order
     *
     * @return string|null
     */
    public function success(Request $request, int $order)
    {
        Log::info('success page request: order ' . $order);
        Log::info(json_encode($request->toArray()));

        return 'success page';
    }

    /**
     * Error page for customer
     */
    public function error(Request $request, int $order)
    {
        Log::info('success page request: order ' . $order);
        Log::info(json_encode($request->toArray()));

        return 'Something went wrong';
    }

    /**
     * @param int $orderId
     *
     * @return OrderModel
     */
    private function getOrder(int $orderId): Order
    {
        return OrderModel::findOrFail($orderId);
    }

    /**
     * @param OrderModel $order
     *
     * @return PaymentModel
     */
    private function getPayment(Order $order): PaymentModel
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
}
