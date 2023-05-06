<?php namespace Books\Orders\Components;

use Books\Book\Models\Award;
use Books\Book\Models\Book;
use Books\Book\Models\Edition;
use Books\Orders\Models\Order as OrderModel;
use Books\Orders\Classes\Enums\OrderStatusEnum;
use Books\Orders\Classes\Services\OrderService;
use Cms\Classes\ComponentBase;
use Dflydev\DotAccessData\Data;
use Exception;
use Flash;
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
    private OrderService $service;

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
        $this->service = app(OrderService::class);

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
            'awards' => $this->awards(),
        ];
    }

    public function onCreateOrder()
    {
        $data = post();
        $user = $this->getUser();

        try {
            $order = $this->getOrder($user, $this->book);

            return [
                '#order_form' => $this->renderPartial('@create-card', [
                    'order' => $order,
                    'book' => $this->book,
                    'awards' => $this->awards(),
                ]),
            ];

        } catch (Exception $e) {
            Log::error($e->getMessage());
            Flash::error($e->getMessage());

            return [];
        }
    }

    private function awards(): array
    {
        return Award::all()->toArray();
    }

    private function getOrder(User $user, Book $book): OrderModel
    {
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
            $order = $this->service->createOrder($user, [$book->ebook]);
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
