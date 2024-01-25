<?php namespace Books\Orders\Components;

use Books\Book\Models\Award;
use Books\Book\Models\Book;
use Books\Book\Models\Edition;
use Books\Book\Models\UserBook;
use Books\Orders\Models\Order as OrderModel;
use Books\Orders\Classes\Services\OrderService;
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
    protected ?Book $book = null;
    protected ?Edition $edition = null;
    private OrderService $orderService;

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

        $this->user = Auth::getUser();
        $book_id = $this->param('book_id');
        $this->book = Book::query()->public()->find($book_id) ?? $this->user?->profile->books()->find($book_id)
            ?? null;
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
        $edition = Edition::findOrFail(post('edition_id'));

        try {
            $order = $this->getOrder($this->getUser(), $edition);

            return [
                '#order_form' => $this->renderPartial('@order_create', [
                    'order' => $order,
                    'book' => $this->book ?? $edition->book,
                    'edition' => $edition,
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
        $edition = Edition::findOrFail(post('edition_id'));

        try {
            $order = $this->getOrder($this->getUser(), $edition);

            return [
                '#order_form' => $this->renderPartial('@order_submit', [
                    'order' => $order,
                    'book' => $this->book,
                    'edition' => $edition,
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

        $edition = Edition::findOrFail(post('edition_id'));

        $order = $this->getOrder($this->getUser(), $edition);

        /**
         * Check already own
         */
        $alreadyOwnBook = UserBook::query()
            ->user($this->getUser())
            ->whereHasMorph('ownable', [Edition::class], function ($q) use ($edition){
                $q->where('id', $edition->id);
            })
            ->exists();

        if ($alreadyOwnBook) {
            $error = 'Ошибка покупки книги. Обновите страницу, возможно книга уже приобретена';
            Log::error($error);
            Flash::error($error);

            return [];
        }

        /**
         * Zero cost
         */
        if ($this->orderService->calculateAmount($order) === 0) {
            $this->orderService->approveOrder($order);

            return Redirect::to($this->orderService->getOrderSuccessRedirectPage($order));
        }

        /**
         * Pay with card
         */
        if ($payType === 'card') {
            return Redirect::to(url('/payment/charge', ['order' => $order->id]));
        }

        /**
         * Pay from balance
         */
        if ($payType === 'balance') {
            try {
                $this->orderService->payFromDeposit($order);

                return Redirect::to($this->orderService->getOrderSuccessRedirectPage($order));

            } catch (Exception $e) {
                Log::error($e->getMessage());
                Flash::error($e->getMessage());

                return [
                    '#orderPayFromBalanceSpawn' => $e->getMessage(),
                ];
            }
        }

        return [];
    }

    public function onOrderAddAward(): array
    {
        $edition = Edition::findOrFail(post('edition_id'));
        $order = $this->getOrder($this->getUser(), $edition);
        $awards = Award::find($this->getAwardsIds());

        $this->orderService->applyAwards($order, $awards, $this->book);

        return [
            '#order_form' => $this->renderPartial('@order_create', [
                'order' => $order,
                'book' => $this->book,
                'edition' => $edition,
                'availableAwards' => $this->getAvailableAwards(),
            ]),
            '#orderTotalAmountSpawn' => $this->orderService->calculateAmount($order) . ' ₽',
        ];
    }

    public function onOrderAddDonation(): array
    {
        $edition = Edition::findOrFail(post('edition_id'));
        $order = $this->getOrder($this->getUser(), $edition);
        $this->orderService->applyAuthorSupport($order, (int)post('donate'));

        return [
            '#order_form' => $this->renderPartial('@order_create', [
                'order' => $order,
                'book' => $this->book,
                'edition' => $edition,
                'availableAwards' => $this->getAvailableAwards(),
            ]),
            '#orderTotalAmountSpawn' => $this->orderService->calculateAmount($order) . ' ₽',
        ];
    }

    public function onOrderAddPromocode(): array
    {
        $edition = Edition::findOrFail(post('edition_id'));
        $order = $this->getOrder($this->getUser(), $edition);
        $promocodeIsApplied = $this->orderService->applyPromocode($order, (string)post('promocode'));

        if (!$promocodeIsApplied) {
            return [
                '#orderPromocodeAppliedResult' => 'Не действителен',
            ];
        }

        return [
            '#order_form' => $this->renderPartial('@order_create', [
                'order' => $order,
                'book' => $this->book,
                'edition' => $edition,
                'availableAwards' => $this->getAvailableAwards(),
            ]),
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

    private function getOrder(User $user, Edition $edition): OrderModel
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
        $order = OrderModel::query()
            ->user($user)
            ->created()
            ->whereHas('products', function ($query) use ($edition) {
                $query->whereHasMorph('orderable', [Edition::class], function ($q) use ($edition) {
                    $q->where('id', $edition->id);
                });
            })
            ->orderBy('id', 'desc')
            ->first();

        /**
         * Иначе - новый заказ
         */
        if (!$order) {
            $order = $this->orderService->createOrder($user);
            $this->orderService->addProducts($order, $edition);
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
