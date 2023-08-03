<?php namespace Books\Blog\Components;

use Cms\Classes\ComponentBase;

/**
 * BlogPostCard Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BlogPostCard extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'BlogPostCard Component',
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
