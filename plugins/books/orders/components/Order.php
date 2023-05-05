<?php namespace Books\Orders\Components;

use Books\Book\Models\Award;
use Books\Book\Models\Book;
use Books\Orders\Classes\Services\OrderService;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

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
        if (!Auth::check()) {
            $this->controller->run('/404');
        }

        $data = post();

        $order = $this->service->createOrder(Auth::getUser(), $data);

        return [
            '#order_form' => $this->renderPartial('@create-card', [
                'order' => $order,
                'book' => $this->book,
                'awards' => $this->awards(),
            ]),
        ];
    }

    private function awards(): array
    {
        return Award::all()->toArray();
    }
}
