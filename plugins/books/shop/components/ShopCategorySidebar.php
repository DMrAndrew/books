<?php namespace Books\Shop\Components;

use Books\Shop\Models\Category;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * ShopCategorySidebar Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class ShopCategorySidebar extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'ShopCategorySidebar Component',
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
        $this->prepareVals();
    }

    private function prepareVals()
    {
        $this->page['categoryId'] = $this->getController()->getRouter()->getParameter('category_id') ?? null;
        $this->page['categories'] = Category::all();
    }
}
