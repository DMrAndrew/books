<?php namespace Books\Payment\Components;

use Cms\Classes\ComponentBase;

/**
 * Payment Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Payment extends ComponentBase
{
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
}
