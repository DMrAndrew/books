<?php namespace Books\Payment\Components;

use Books\Orders\Classes\Services\OrderService;
use Books\Orders\Models\Order as OrderModel;
use Books\Payment\Classes\PaymentService;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * Payment Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Payment extends ComponentBase
{
    private ?OrderModel $order;
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

        $this->charge();
    }
}
