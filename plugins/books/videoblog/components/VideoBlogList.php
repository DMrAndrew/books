<?php namespace Books\Videoblog\Components;

use Books\Videoblog\Models\Videoblog;
use Cms\Classes\ComponentBase;

/**
 * BlogList Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class VideoBlogList extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'VideoBlogList Component',
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
        $this->page['posts'] = Videoblog
            ::orderByDesc('created_at')
            ->paginate((int) $this->property('recordsPerPage', 16));
    }
}
