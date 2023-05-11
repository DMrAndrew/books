<?php
declare(strict_types=1);

namespace Books\Payment\Controllers;

use Books\Orders\Classes\Services\OrderService;
use Books\Orders\Models\Order;
use Books\Orders\Models\Order as OrderModel;
use Books\Payment\Classes\PaymentService;
use Books\Payment\Models\Payment as PaymentModel;
use Cms\Classes\CmsException;
use Cms\Classes\Controller;
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
    public function charge(Request $request, int $order)
    {
        $order = $this->getOrder($order);

        try {
            // create payment
            $payment = $this->getPayment($order);

            // run yookassa payment
            $response = $this->paymentService->gateway->purchase([
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

        return response();
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function webhook(Request $request)
    {
        Log::info('service webhook');
        Log::info(json_encode($request->toArray()));

        // Once the transaction has been approved, we need to complete it.
        if ($request->input('paymentId') && $request->input('PayerID'))
        {
            $transaction = $this->gateway->completePurchase(array(
                'payer_id'             => $request->input('PayerID'),
                'transactionReference' => $request->input('paymentId'),
            ));
            $response = $transaction->send();

            if ($response->isSuccessful())
            {
                // The customer has successfully paid.
                $arr_body = $response->getData();

                Log::info('successful payment request data:');
                Log::info($arr_body);

                // Insert transaction data into the database
                // todo

                return "Payment is successful. Your transaction id is: ". $arr_body['id'];
            } else {
                return $response->getMessage();
            }
        } else {
            return 'Transaction is declined';
        }
    }

    /**
     * Charge a payment and store the transaction.
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
     * Error Handling.
     */
    public function error(Request $request, int $order)
    {
        Log::info('success page request: order ' . $order);
        Log::info(json_encode($request->toArray()));

        return 'Something went wrong';
    }

    private function getOrder(int $orderId): Order
    {
        return OrderModel::findOrFail($orderId);
    }

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
