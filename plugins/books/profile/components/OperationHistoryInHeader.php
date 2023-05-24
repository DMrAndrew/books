<?php namespace Books\Profile\Components;

use Cms\Classes\ComponentBase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
                'title' => 'Операций в модальном окне',
                'comment' => 'Количество отображаемых последних операций',
                'default' => 2,
            ],
        ];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
    }

    public function onRun(): void
    {
        $this->page['operations'] = $this->getOperations();
    }

    private function getOperations()
    {
        return Auth::getUser()
            ->operations()
            ->limit((int) $this->property('operationsPerView', 2))
            ->get();
    }
}
