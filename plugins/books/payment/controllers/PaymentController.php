<?php
declare(strict_types=1);

namespace Books\Payment\Controllers;

use Backend\Classes\Controller;
use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Classes\Services\OrderService;
use Books\Payment\Models\Payment as PaymentModel;
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
    /**
     * CloudPayments response codes
     * https://developers.cloudpayments.ru/#check
     */
    CONST STATUS_CODE_OK = 0; //Платеж может быть проведен
    CONST STATUS_CODE_INVALID_INVOICE_ID = 10; //Неверный номер заказа
    CONST STATUS_CODE_INVALID_ACCOUNT_ID = 11; // Некорректный AccountId
    CONST STATUS_CODE_INVALID_AMOUNT = 12; // Неверная сумма
    CONST STATUS_CODE_ERROR = 13; // Платеж не может быть принят

    private OrderService $orderService;

    public function __construct()
    {
        parent::__construct();

        $this->orderService = app(OrderService::class);
    }

    /**
     * Webhook for Check
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $this->logWebhookProcessing('Request data Check', $request);

        try {
            $resultCode = $this->runPayment($request, true);

            return response()->json(['code' => $resultCode]);
        } catch (Exception $e) {
            $this->logWebhookProcessing($e->getMessage());

            return response()->json(['code' => self::STATUS_CODE_ERROR]);
        }
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function pay(Request $request): JsonResponse
    {
        $this->logWebhookProcessing('Request data Pay', $request);

        try {
            $resultCode = $this->runPayment($request, false);

            return response()->json(['code' => $resultCode]);
        } catch (Exception $e) {
            $this->logWebhookProcessing($e->getMessage());

            return response()->json(['code' => self::STATUS_CODE_ERROR]);
        }
    }

    /**
     * @param Request $request
     * @param bool $checkOnly
     *
     * @return int $resultCode
     * @throws Exception
     */
    private function runPayment(Request $request, bool $checkOnly = true): int
    {
        $paymentWebhookData = $request ?? null;

        if ($paymentWebhookData != null || is_array($paymentWebhookData)) {
                $transactionId = $paymentWebhookData['TransactionId'];
                $paymentData = json_decode($paymentWebhookData['Data'], true);

                $this->logWebhookProcessing('paymentData', $paymentData);

                /**
                 * Get payment model
                 */
                $payment = $this->getPayment($paymentData['paymentId']);
                $payment->update(['transaction_id' => $transactionId]);

                /**
                 * Verify payment
                 */
                if (! $this->validRequestHashHMAC($request)) {
                    $this->logWebhookProcessing(
                        'Verification failed',
                        "HMac-hash from Cloudpayments does not match the hash calculated on the client for transaction {$transactionId}"
                    );

                    return self::STATUS_CODE_ERROR;
                }

                if (! $this->validPaymentInvoiceId($payment, $paymentWebhookData)) {
                    $this->logWebhookProcessing(
                        'Verification failed',
                        "Invalid order {$paymentWebhookData['InvoiceId']} for transaction {$transactionId}"
                    );

                    return self::STATUS_CODE_INVALID_INVOICE_ID;
                }

                if (! $this->validPaymentAccountId($payment, $paymentWebhookData)) {
                    $this->logWebhookProcessing(
                        'Verification failed',
                        "Invalid AccountId {$paymentWebhookData['AccountId']} for transaction {$transactionId}"
                    );

                    return self::STATUS_CODE_INVALID_ACCOUNT_ID;
                }

                if (! $this->validPaymentAmount($payment, $paymentWebhookData)) {
                    $this->logWebhookProcessing(
                        'Verification failed',
                        "Payment amount does not match the order amount for transaction {$transactionId}"
                    );

                    return self::STATUS_CODE_INVALID_AMOUNT;
                }

                /**
                 * Update payment status
                 */
                if (! $checkOnly) {
                    $this->updatePaymentStatus($payment, $paymentWebhookData);
                }

            /**
             * Response OK
             */
            return self::STATUS_CODE_OK;

        } else {
            throw new Exception("Cant get payment data from request");
        }
    }

    /**
     * @param string $paymentId
     *
     * @return PaymentModel
     * @throws Exception
     */
    private function getPayment(string $paymentId): PaymentModel
    {
        if (! isset($paymentId)) {
            throw new Exception("Field paymentId is required in Data array");
        }

        $payment = PaymentModel::where('payment_id', $paymentId)->first();
        if (! $payment) {
            throw new Exception("Cant resolve payment from request data");
        }

        return $payment;
    }

    /**
     * @param PaymentModel $payment
     * @param Request $paymentWebhookData
     *
     * @return void
     * @throws Exception
     */
    private function updatePaymentStatus(PaymentModel $payment, Request $paymentWebhookData): void
    {
        $paymentStatus = $paymentWebhookData['Status'];
        $order = $payment->order;
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

                    if (! $isApproved) {
                        throw new Exception("Something went wrong with completing order #{$order->id}");
                    }
                }
                break;

            // отменен
            case 'Cancelled':
                if ($order->status != OrderStatusEnum::CANCELED->value) {
                    $this->orderService->updateOrderstatus($order, $orderStatus);
                    $isCancelled = $this->orderService->cancelOrder($order);

                    if (! $isCancelled) {
                        throw new Exception("Something went wrong with cancelling order #{$order->id}");
                    }
                }
                break;
        }
    }

    /**
     * https://developers.cloudpayments.ru/#proverka-uvedomleniy
     *
     * @param Request $request
     *
     * @return bool
     */
    private function validRequestHashHMAC(Request $request): bool
    {
        if (config('cloudpayments.disable_payment_hmac_hash_validation')) {
            return true;
        }

        /**
         * Validation string from CloudPayment service
         */
        $hmacRequestHeader = $request->header('Content-HMAC');
        if (empty($hmacRequestHeader)) {
            return false;
        }

        /**
         * Validation string calculated on client side
         */
        $hashKeyFromApiSecret = config('cloudpayments.apiSecret');
        $requestBody = $request->getContent();

        $clientHmac = base64_encode(
            hash_hmac(
                'sha256',
                $requestBody,
                $hashKeyFromApiSecret,
                true
            )
        );

        return $hmacRequestHeader === $clientHmac;
    }

    /**
     * @param PaymentModel $payment
     * @param Request $paymentWebhookData
     *
     * @return bool
     */
    private function validPaymentInvoiceId(PaymentModel $payment, Request $paymentWebhookData): bool
    {
        return $payment->order->id == (int)$paymentWebhookData['InvoiceId'];
    }

    /**
     * @param PaymentModel $payment
     * @param Request $paymentWebhookData
     *
     * @return bool
     */
    private function validPaymentAccountId(PaymentModel $payment, Request $paymentWebhookData): bool
    {
        return $payment->order->user_id == (int)$paymentWebhookData['AccountId'];
    }

    /**
     * @param PaymentModel $payment
     * @param Request $paymentWebhookData
     *
     * @return bool
     */
    private function validPaymentAmount(PaymentModel $payment, Request $paymentWebhookData): bool
    {
        $orderAmount = $this->orderService->calculateAmount($payment->order);

        return $orderAmount === (int)$paymentWebhookData['Amount'];
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
