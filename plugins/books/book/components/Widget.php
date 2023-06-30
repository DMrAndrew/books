<?php

namespace Books\Book\Components;

use Books\Book\Classes\Enums\WidgetEnum;
use Books\Book\Classes\WidgetService;
use Cms\Classes\ComponentBase;
use Exception;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Widget Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Widget extends ComponentBase
{
    protected WidgetService $service;

    protected ?User $user;

    public function componentDetails()
    {
        return [
            'name' => 'Widget Component',
            'description' => 'No description provided yet...',
        ];
    }

    public function init()
    {
        $this->user = Auth::getUser();
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    /**
     * @param WidgetEnum $widget
     * @param mixed ...$options
     *
     * @throws Exception
     */
    public function setUpWidget(WidgetEnum $widget, ...$options): void
    {
        $options['user'] ??= $this->user;
        $this->service = new WidgetService($widget, ...$options);
    }

    /**
     * @throws Exception
     */
    public function onRender()
    {
        $this->page['widget'] = $this->service->toWidgetParams();
    }
}
