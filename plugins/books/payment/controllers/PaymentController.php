<?php
declare(strict_types=1);

namespace Books\Payment\Controllers;

use Backend\Classes\Controller;
use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Classes\Services\OrderService;
use Books\Orders\Models\Order;
use Books\Orders\Models\Order as OrderModel;
use Books\Payment\Models\Payment as PaymentModel;
use Db;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CloudPayments - https://developers.cloudpayments.ru
 *
 * тестовые карты с разным статусом ответа:
 * https://developers.cloudpayments.ru/#testirovanie
 */
class PaymentController extends Controller
{
    private OrderService $orderService;

    public function __construct()
    {
        parent::__construct();

        $this->orderService = app(OrderService::class);
    }

    public function index()
    {
        return 'payment controller index()';
    }

    /**
     * Webhook from payment gateway for updating status
     *
     * @param Request $request
     */
    public function webhook(Request $request)
    {
        if (config('app.log_payment_gateway_webhook')) {
            Log::channel('log_payment_gateway_webhook')->info($request);
        }

        try {
            // Once the transaction has been approved, we need to complete it
            $object = $request->object ?? null;

            if ($object) {
                DB::transaction( function() use ($object) {

                    $transactionId = $object['metadata']['transactionId'];
                    $paymentStatus = $object['status'];

                    $payment = PaymentModel::where('payment_id', $transactionId)->firstOrFail();
                    $order = $payment->order;

                    /** update payment status */
                    $payment->update(['payment_status' => $paymentStatus]);

                    /**
                     * update order status
                     * available YooKassa payment statuses - https://yookassa.ru/developers/using-api/webhooks#events-basics
                     */
                    $orderStatus = match ($paymentStatus) {
                        'waiting_for_capture' => OrderStatusEnum::PENDING,
                        'succeeded' => OrderStatusEnum::PAID,
                        'canceled' => OrderStatusEnum::CANCELED,
                    };

                    switch ($paymentStatus) {
                        // ожидает подтверждения
                        case 'waiting_for_capture':
                            $this->orderService->updateOrderstatus($order, $orderStatus);
                            break;

                        // успешно
                        case 'succeeded':
                            if ($order->status != OrderStatusEnum::PAID->value) {

                                $paymentAmount = (int) $object['amount']['value'];
                                $orderAmount = $this->orderService->calculateAmount($order);

                                if ($paymentAmount !== $orderAmount) {
                                    throw new Exception("Payment amount does not match the order amount. Order #{$order->id}");
                                }

                                $this->orderService->updateOrderstatus($order, $orderStatus);
                                $isApproved = $this->orderService->approveOrder($order);

                                if (!$isApproved) {
                                    throw new Exception("Something went wrong with updating order #{$order->id}");
                                }
                            }
                            break;

                        // отменен
                        case 'canceled':
                            if ($order->status != OrderStatusEnum::CANCELED->value) {
                                $this->orderService->updateOrderstatus($order, $orderStatus);
                                $isCancelled = $this->orderService->cancelOrder($order);

                                if (!$isCancelled) {
                                    throw new Exception("Something went wrong with cancelling order #{$order->id}");
                                }
                            }
                            break;
                    }
                });
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());

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
