<?php namespace Books\Blog\Components;

use Books\Blog\Models\Post;
use Cms\Classes\ComponentBase;

/**
 * BlogList Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BlogList extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'BlogList Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [
            'recordsPerPage' => [
                'title' => 'Публикаций на странице',
                'comment' => 'Количество публикаций отображаемых на одной странице',
                'default' => 16,
            ],
        ];
    }

    public function init()
    {
        $this->page['posts'] = Post
            ::orderByDesc('created_at')
            ->paginate((int) $this->property('recordsPerPage', 16));
    }
}
