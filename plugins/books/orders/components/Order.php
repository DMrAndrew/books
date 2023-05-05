<?php namespace Books\Orders\Components;

use Cms\Classes\ComponentBase;

/**
 * Order Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Order extends ComponentBase
{
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

    public function onCreateOrder()
    {
        $data = post();

        dd($data);

        return 'createOrder';
    }
}
