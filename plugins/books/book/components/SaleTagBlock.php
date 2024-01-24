<?php namespace Books\Book\Components;

use Cms\Classes\ComponentBase;

/**
 * SaleTagBlock Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class SaleTagBlock extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'SaleTagBlock Component',
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

    public function onRun()
    {
        parent::onRun();
        $this->defineProperties();
    }
}
