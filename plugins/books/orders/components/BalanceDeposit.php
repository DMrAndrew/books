<?php namespace Books\Orders\Components;

use Books\Orders\Classes\Services\OrderService;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Support\Facades\Redirect;
use Log;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * BalanceDeposit Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BalanceDeposit extends ComponentBase
{
    private OrderService $orderService;

    public function componentDetails()
    {
        return [
            'name' => 'BalanceDeposit Component',
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

    public function init(): void
    {
        $this->orderService = app(OrderService::class);
    }

    public function onCreateDeposit(): array
    {
        return [
            '#deposit_form' => $this->renderPartial('@deposit_create')
        ];
    }

    public function onDepositSubmit()
    {
        try {
            $depositAmount = post('deposit');

            if ($depositAmount == null || !is_numeric($depositAmount) || $depositAmount <= 0) {
                throw new Exception('Invalid deposit amount');
            }

            $order = $this->orderService->createOrder($this->getUser());
            $this->orderService->addDeposit($order, $depositAmount);

            return Redirect::to(url('/payment/charge', ['order' => $order->id]));

        } catch (Exception $e) {
            Log::error($e->getMessage());
            Flash::error($e->getMessage());

            return [];
        }
    }

    private function getUser(): User
    {
        if (!Auth::check()) {
            $this->controller->run('/404');
        }

        return Auth::getUser();
    }
}
