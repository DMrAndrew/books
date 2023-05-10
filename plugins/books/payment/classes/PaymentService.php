<?php

namespace Books\Payment\Classes;

use Books\Orders\Classes\Services\OrderService;
use Books\Payment\Contracts\PaymentService as PaymentServiceContract;
use Books\Orders\Models\Order;
use Books\Payment\Models\Payment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Log;
use Omnipay\Omnipay;

/**
 * YooKassa - https://yookassa.ru/docs
 *
 * тестовые карты с разным статусом ответа:
 * https://yookassa.ru/developers/payment-acceptance/testing-and-going-live/testing?ysclid=lgwcau6d1r312270278#test-bank-card-success
 * Успешная оплата: 5555555555554477
 */
class PaymentService implements PaymentServiceContract
{
    private $gateway;
    private OrderService $orderService;

    public function __construct()
    {
        $this->gateway = Omnipay::create('YooKassa');
        $this->gateway->setShopId(env('YOOKASSA_SHOP_ID'));
        $this->gateway->setSecret(env('YOOKASSA_SECRET'));

        $this->orderService = app(OrderService::class);
    }

    /**
     * Call a view.
     */
    public function index()
    {
        return 'payment index';
    }

    /**
     * Initiate a payment
     *
     * @return string|void|null
     */
    public function charge(Order $order)
    {
        try {
            $response = $this->gateway->purchase([
                'amount' => $this->orderService->calculateAmount($order),
                'currency' => Payment::CURRENCY,
                'description' => "Заказ №{$order->id}",

                //'status' => 'pending',
                'capture' => false,
                'recipient' => 'blaqdog@mail.ru',
                'transactionId' => Str::uuid(),

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
     * @param Request $request
     *
     * @return Response
     */
    public function webhook(Request $request): Response
    {
        Log::info('service webhook');
        Log::info($request);

        return response();
    }

    /**
     * Charge a payment and store the transaction.
     *
     * @param Request $request
     *
     * @return string|null
     */
    public function success(Request $request)
    {
        Log::info('success page request:');
        Log::info($request);

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
//                $payment = new Payment;
//                $payment->payment_id = $arr_body['id'];
//                $payment->payer_id = $arr_body['payer']['payer_info']['payer_id'];
//                $payment->payer_email = $arr_body['payer']['payer_info']['email'];
//                $payment->amount = $arr_body['transactions'][0]['amount']['total'];
//                $payment->currency = 'RUB';
//                $payment->payment_status = $arr_body['state'];
//                $payment->save();

                return "Payment is successful. Your transaction id is: ". $arr_body['id'];
            } else {
                return $response->getMessage();
            }
        } else {
            return 'Transaction is declined';
        }
    }

    /**
     * Error Handling.
     */
    public function error()
    {
        return 'User cancelled the payment.';
    }
}
