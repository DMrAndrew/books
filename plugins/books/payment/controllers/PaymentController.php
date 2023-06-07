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
use Illuminate\Http\JsonResponse;
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

    /**
     * Webhook from payment gateway for updating status
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function webhook(Request $request)
    {
        $this->logWebhookProcessing('Request data', $request);

        try {
            // Once the transaction has been approved, we need to complete it
            $paymentWebhookData = $request ?? null;

            if ($paymentWebhookData != null || is_array($paymentWebhookData)) {
                DB::transaction( function() use ($paymentWebhookData) {

                    $transactionId = $paymentWebhookData['TransactionId'];
                    $paymentData = json_decode($paymentWebhookData['Data'], true);

                    $this->logWebhookProcessing('paymentData', $paymentData);

                    /**
                     * Get payment model
                     */
                    if (!isset($paymentData['paymentId'])) {
                        throw new Exception("Field paymentId is required in Data array");
                    }

                    $payment = PaymentModel::where('payment_id', $paymentData['paymentId'])->first();
                    if (!$payment) {
                        throw new Exception("Cant resolve payment from request data");
                    }
                    $payment->update(['transaction_id' => $transactionId]);

                    /**
                     * Verify payment
                     */
                    $order = $payment->order;

                    // Номер заказа
                    $paymentOrderId = (int)$paymentWebhookData['InvoiceId'];
                    if ($order->id != $paymentOrderId) {
                        $this->logWebhookProcessing('Verification failed', "Invalid order {$paymentWebhookData['InvoiceId']} for transaction {$transactionId}");

                        return response()->json(['code' => '10']);
                    }

                    // Пользователь
                    $paymentUserId = (int)$paymentWebhookData['AccountId'];
                    if ($order->user_id != $paymentUserId) {
                        $this->logWebhookProcessing('Verification failed', "Invalid AccountId {$paymentWebhookData['AccountId']} for transaction {$transactionId}");

                        return response()->json(['code' => '11']);
                    }

                    // Сумма
                    $paymentAmount = (int)$paymentWebhookData['Amount'];
                    $orderAmount = $this->orderService->calculateAmount($order);
                    if ($paymentAmount !== $orderAmount) {
                        $this->logWebhookProcessing('Verification failed', "Payment amount does not match the order amount for transaction {$transactionId}");

                        return response()->json(['code' => '12']);
                    }

                    /**
                     * Update payment status
                     */
                    $paymentStatus = $paymentWebhookData['Status'];
                    $payment->update(['payment_status' => $paymentStatus]);

                    /**
                     * update order status ( https://developers.cloudpayments.ru/#statusy-operatsiy )
                     */
                    $orderStatus = match ($paymentStatus) {
                        'AwaitingAuthentication', 'Authorized' => OrderStatusEnum::PENDING,
                        'Completed' => OrderStatusEnum::PAID,
                        'Cancelled' => OrderStatusEnum::CANCELED,
                    };

                    switch ($paymentStatus) {
                        // ожидает подтверждения
                        case 'AwaitingAuthentication':
                        case 'Authorized':
                            $this->orderService->updateOrderstatus($order, $orderStatus);
                            break;

                        // успешно
                        case 'Completed':
                            if ($order->status != OrderStatusEnum::PAID->value) {
                                $this->orderService->updateOrderstatus($order, $orderStatus);
                                $isApproved = $this->orderService->approveOrder($order);

                                if (!$isApproved) {
                                    throw new Exception("Something went wrong with completing order #{$order->id}");
                                }
                            }
                            break;

                        // отменен
                        case 'Cancelled':
                            if ($order->status != OrderStatusEnum::CANCELED->value) {
                                $this->orderService->updateOrderstatus($order, $orderStatus);
                                $isCancelled = $this->orderService->cancelOrder($order);

                                if (!$isCancelled) {
                                    throw new Exception("Something went wrong with cancelling order #{$order->id}");
                                }
                            }
                            break;
                    }

                    return true;
                });

                /**
                 * Response OK
                 */
                return response()->json(['code' => '0']);

            } else {
                throw new Exception("Cant get payment data from request");
            }
        } catch (Exception $e) {
            $this->logWebhookProcessing($e->getMessage());

            /** response codes - https://developers.cloudpayments.ru/#check */
            return response()->json(['code' => '13']);
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

    /**
     * @param string $name
     * @param mixed $data
     *
     * @return void
     */
    private function logWebhookProcessing(string $name, mixed $data = null): void
    {
        if (config('app.log_payment_gateway_webhook')) {
            Log::channel('log_payment_gateway_webhook')->info($name);
            if ($data != null) {
                Log::channel('log_payment_gateway_webhook')->info($data);
            }
        }
    }
}
