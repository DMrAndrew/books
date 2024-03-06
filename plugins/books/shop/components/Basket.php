<?php namespace Books\Shop\Components;

use Cms\Classes\ComponentBase;

/**
 * Basket Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Basket extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Basket Component',
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
