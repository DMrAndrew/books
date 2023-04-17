<?php namespace Books\Book\Components;

use Cms\Classes\ComponentBase;

/**
 * Promocode Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Promocode extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Promocode Component',
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
