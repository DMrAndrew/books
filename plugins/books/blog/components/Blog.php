<?php namespace Books\Blog\Components;

use Cms\Classes\ComponentBase;

/**
 * Blog Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Blog extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Blog Component',
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
