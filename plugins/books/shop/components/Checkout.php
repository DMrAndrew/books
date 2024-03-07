<?php namespace Books\Shop\Components;

use ApplicationException;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Books\Profile\Models\Profile;
use Books\Shop\Models\Country;
use Books\Shop\Models\Order;
use Books\Shop\Models\OrderItems;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;
use Redirect;
use ValidationException;
use Validator;

/**
 * Checkout Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Checkout extends ComponentBase
{
    private $userProfile;

    public function componentDetails()
    {
        return [
            'name' => 'Checkout Component',
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
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->userProfile = Auth::getUser()->profile;
        $orderItems = OrderItems::where('seller_id', $this->param('seller_id'))
            ->where('buyer_id', $this->userProfile->getKey())
            ->whereNull('order_id')
            ->get();
        $amount = 0;
        $orderItems->groupBy('seller_id')->each(function ($items, $key) use (&$amount) {
            $amount = $amount + $items->sum('price');
        });
        $this->page['amount'] = $amount;
        $this->page['countries'] = Country::all(['id', 'name']);
        $this->page['buyer_id'] = $this->userProfile->getKey();
        $this->page['seller_id'] = Profile::findOrfail($this->param('seller_id'))->getKey();
        $this->registerBreadcrumbs();
    }

    /**
     * @return void
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('lc-shop-create', static function (BreadcrumbsGenerator $trail, $params): void {
            $trail->parent('lc');
            $trail->push('Магазин', url('/shop'));
            $trail->push('Оформление заказа');
        });
    }

    public function onSave()
    {
        try {
            if (!Auth::check()) {
                throw new ApplicationException('Требуется авторизация');
            }

            $order = new Order();
            $data = post();

            $validator = Validator::make(
                $data,
                $order->rules,
                [],
                $order->attributeNames
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $order->fill($validator->valid());
            $order->save();

            $orderItems = OrderItems::where('seller_id', (int)post('seller_id'))
                ->where('buyer_id', $this->userProfile->getKey())
                ->get();

            $order->products()->saveMany($orderItems);

            Flash::success('Заказ успешно создан');

            return Redirect::to('/shop');
        } catch (Exception $e) {
            Flash::error($e->getMessage());
        }
    }
}
