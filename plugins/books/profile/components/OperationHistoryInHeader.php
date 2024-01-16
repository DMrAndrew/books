<?php namespace Books\Profile\Components;

use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * OperationHistory Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class OperationHistoryInHeader extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'История операций в шапке',
            'description' => 'Отображение списка последних операций в выпадающем окне'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [
            'operationsPerView' => [
                'title' => 'История операций в модальном окне',
                'comment' => 'Количество отображаемых последних операций',
                'default' => 10,
            ],
        ];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
    }

    /**
     * @return array
     */
    public function onViewHeaderOperations(): array
    {
        if (! Auth::getUser()) {
            return [];
        }

        $this->page['operations'] = Auth::getUser()
            ->operations()
            ->limit((int) $this->property('operationsPerView', 10))
            ->get();

        return [
            '#operations-in-header-list' => $this->renderPartial('@list'),
            '#operations-in-header-list-mobile' => $this->renderPartial('@list-mobile'),
        ];
    }
}
