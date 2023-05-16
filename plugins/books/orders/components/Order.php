<?php namespace Books\Orders\Components;

use Books\Book\Models\Award;
use Books\Book\Models\Book;
use Books\Book\Models\Edition;
use Books\Orders\Models\Order as OrderModel;
use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Classes\Services\OrderService;
use Books\Payment\Classes\PaymentService;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Support\Facades\Redirect;
use Log;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Order Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Order extends ComponentBase
{
    protected Book $book;
    private OrderService $orderService;
    private PaymentService $paymentService;

    public function componentDetails()
    {
        return [
            'name' => 'Order Component',
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
        $this->paymentService = app(PaymentService::class);

        $this->user = Auth::getUser();
        $book_id = $this->param('book_id');
        $this->book = Book::query()->public()->find($book_id) ?? $this->user?->profile->books()->find($book_id)
            ?? abort(404);
    }

    public function onRender()
    {
        foreach ($this->vals() as $key => $val) {
            $this->page[$key] = $val;
        }
    }

    public function vals()
    {
        return [
            'book' => $this->book,
            'availableAwards' => $this->getAvailableAwards(),
        ];
    }

    public function onCreateOrder(): array
    {
        try {
            $order = $this->getOrder($this->getUser(), $this->book);

            return [
                '#order_form' => $this->renderPartial('@order_create', [
                    'order' => $order,
                    'book' => $this->book,
                    'availableAwards' => $this->getAvailableAwards(),
                ]),
                '#orderTotalAmountSpawn' => $this->orderService->calculateAmount($order) . ' ₽',
            ];

        } catch (Exception $e) {
            Log::error($e->getMessage());
            Flash::error($e->getMessage());

            return [];
        }
    }

    public function onOrderSubmit(): array
    {
        try {
            $order = $this->getOrder($this->getUser(), $this->book);

            return [
                '#order_form' => $this->renderPartial('@order_submit', [
                    'order' => $order,
                    'book' => $this->book,
                    'availableAwards' => $this->getAvailableAwards(),
                ]),
                '#orderTotalAmountSpawn' => $this->orderService->calculateAmount($order) . ' ₽',
            ];

        } catch (Exception $e) {
            Log::error($e->getMessage());
            Flash::error($e->getMessage());

            return [];
        }
    }

    public function onPayOrder()
    {
        $payType = post('payType');
        if (!in_array($payType, ['balance', 'card'])) {
            return [];
        }

        $order = $this->getOrder($this->getUser(), $this->book);

        if ($payType === 'card') {
            return Redirect::to(route('payment.charge', ['order' => $order->id]));
        }
    }

    public function onOrderAddAward(): array
    {
        $order = $this->getOrder($this->getUser(), $this->book);
        $awards = Award::find($this->getAwardsIds());

        $this->orderService->applyAwards($order, $awards);

        return [
            '#order_form' => $this->renderPartial('@order_create', [
                'order' => $order,
                'book' => $this->book,
                'availableAwards' => $this->getAvailableAwards(),
            ]),
            '#orderTotalAmountSpawn' => $this->orderService->calculateAmount($order) . ' ₽',
        ];
    }

    public function onOrderAddDonation(): array
    {
        $order = $this->getOrder($this->getUser(), $this->book);
        $this->orderService->applyAuthorSupport($order, (int) post('donate'));

        return [
            '#order_form' => $this->renderPartial('@order_create', [
                'order' => $order,
                'book' => $this->book,
                'availableAwards' => $this->getAvailableAwards(),
            ]),
            '#orderTotalAmountSpawn' => $this->orderService->calculateAmount($order) . ' ₽',
        ];
    }
    public function onOrderAddPromocode(): array
    {
        $order = $this->getOrder($this->getUser(), $this->book);
        $promocodeIsApplied = $this->orderService->applyPromocode($order, (string) post('promocode'));

        return [
//            '#order_form' => $this->renderPartial('@order_create', [
//                'order' => $order,
//                'book' => $this->book,
//                'availableAwards' => $this->getAvailableAwards(),
//            ]),
            '#orderPromocodeApplied' => (string) post('promocode'),
            '#orderPromocodeAppliedResult' => $promocodeIsApplied ? 'Применен' : 'Не действителен',
            '#orderTotalAmountSpawn' => $this->orderService->calculateAmount($order) . ' ₽',
        ];
    }

    private function getAwardsIds(): array
    {
        return collect(post('awards'))->filter(fn($i) => !!$i)->keys()->toArray();
    }

    private function getAvailableAwards(): array
    {
        return Award::all()->toArray();
    }

    private function getOrder(User $user, Book $book): OrderModel
    {
        /**
         * Если редактируем поля
         */
        if (post('order_id') !== null) {
            return OrderModel::findOrFail(post('order_id'));
        }

        /**
         * Если пользователь оставил неоплаченный заказ - возвращаемся к нему
         */
        $order = OrderModel
            ::where('user_id', $user->id)
            ->whereStatus(OrderStatusEnum::CREATED)
            ->whereHas('products', function($query) use ($book){
                $query->whereHasMorph('orderable', [Edition::class], function($q) use ($book){
                    $q->where('id', $book->ebook->id);
                });
            })
            ->first();

        /**
         * Иначе - новый заказ
         */
        if (!$order) {
            $order = $this->orderService->createOrder($user);
            $this->orderService->addProducts($order, collect([$book->ebook]));
        }

        return $order;
    }

    private function getUser(): User
    {
        if (!Auth::check()) {
            $this->controller->run('/404');
        }

        return Auth::getUser();
    }
}
