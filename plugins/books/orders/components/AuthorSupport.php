<?php namespace Books\Orders\Components;

use Books\Book\Models\Award;
use Books\Book\Models\Book;
use Books\Book\Models\Donation;
use Books\Book\Models\Edition;
use Books\Orders\Classes\Services\OrderService;
use Books\Orders\Models\Order as OrderModel;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Support\Facades\Redirect;
use Log;
use October\Rain\Support\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * AuthorSupport Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class AuthorSupport extends ComponentBase
{
    private OrderService $orderService;

    public function componentDetails()
    {
        return [
            'name' => 'AuthorSupport',
            'description' => 'Поддержка автора (оплата)',
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

    public function onAuthorSupportCreate(): array
    {
        /**
         * From /author-page
         */
        $profileId = (int)$this->param('profile_id');
        if ($profileId) {
            return [
                '#authors_support_form' => $this->renderPartial('@support_create', [
                    'profile_ids' => [
                        (int)$this->param('profile_id')
                    ],
                ]),
            ];
        }

        /**
         * From /book-card
         */

        if ($book = Book::query()->with('authors.profile')->findOrFail((int)$this->param('book_id'))) {

            $profileIds = $book->authors
                ->map->profile
                ->pluck('id')
                ->unique()->values()
                ->toArray();
//            $book->authors->each(function ($author) use ($profiles) {
//                $profiles->push($author->profile);
//            });
//
//            $profileIds = $profiles->pluck('id')->unique()->toArray();

            return [
                '#authors_support_form' => $this->renderPartial('@support_create', [
                    'profile_ids' => $profileIds,
                ]),
            ];
        }
    }

    public function onAuthorSupportSubmit(): array
    {
        $donateAmount = (int)post('donate');
        if ($donateAmount <= 0) {
            Flash::error('Необходимо ввести сумму');

            return [];
        }

        $profileIds = explode(',', post('profile_ids'));
        if (count($profileIds) == 0) {
            Flash::error('Необходимо указать автора, которого вы хотите поддержать');

            return [];
        }

        try {
            $order = $this->getOrder($this->getUser());

            $authorsRewardPartRounded = $this->orderService->getRewardPartRounded($donateAmount, count($profileIds));

            foreach (Profile::find($profileIds) as $profile) {
                $this->orderService->applyAuthorSupport($order, $authorsRewardPartRounded, $profile);
            }

            return [
                '#authors_support_form' => $this->renderPartial('@support_submit'),
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

        $order = $this->getOrder($this->getUser());

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

        return [];
    }

    private function getOrder(User $user): OrderModel
    {
        /**
         * Если пользователь оставил неоплаченный заказ - возвращаемся к нему
         */
        $order = OrderModel::query()
            ->user($user)
            ->created()
            ->whereHas('products', function ($query) {
                $query->whereHasMorph('orderable', [Donation::class]);
            })
            /**
             * Заказ, который содержит только Поддержку (исключаем книги)
             * на случай, если есть оставленный заказ с книгой
             */
            ->whereDoesntHave('products', function ($query) {
                $query->whereHasMorph('orderable', [Edition::class, Award::class]);
            })
            ->first();

        /**
         * Иначе - новый заказ
         */
        if (!$order) {
            $order = $this->orderService->createOrder($user);
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
