<?php namespace Books\Orders\Components;

use Books\Book\Models\Award;
use Books\Book\Models\Book;
use Books\Orders\Classes\Services\OrderService;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Support\Facades\Redirect;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * BuyAwards Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BuyAwards extends ComponentBase
{
    protected ?Book $book;
    protected ?User $user;
    private OrderService $orderService;

    public function componentDetails()
    {
        return [
            'name' => 'BuyAwards Component',
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
        $this->book = Book::query()->public()->find($this->param('book_id'));
        $this->user = Auth::getUser();
    }

    public function onBuyAward()
    {
        if (empty($this->getAwardsIds())) {
            Flash::error('Необходимо выбрать награду');

            return [];
        }

        $payType = post('payType');
        if (!in_array($payType, ['balance', 'card'])) {
            return [];
        }

        $order = $this->orderService->createOrder($this->user);
        $awards = Award::find($this->getAwardsIds());
        $this->orderService->applyAwards($order, $awards, $this->book);

        if ($payType === 'card') {
            return Redirect::to(route('payment.charge', ['order' => $order->id]));
        }

        if ($payType === 'balance') {
            try {
                $this->orderService->payFromDeposit($order);

                return Redirect::to($this->currentPageUrl());

            } catch (Exception $e) {
                return [
                    '#orderPayFromBalanceSpawn' => $e->getMessage(),
                ];
            }
        }

        return $this->render();
    }

    public function getAwardsIds(): array
    {
        return collect(post('awards'))->filter(fn($i) => !!$i)->keys()->toArray();
    }
}
