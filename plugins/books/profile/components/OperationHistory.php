<?php namespace Books\Profile\Components;

use Cms\Classes\ComponentBase;

/**
 * OperationHistory Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class OperationHistory extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'OperationHistory Component',
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
