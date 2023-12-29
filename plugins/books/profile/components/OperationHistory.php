<?php namespace Books\Profile\Components;

use Cms\Classes\ComponentBase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RainLab\User\Facades\Auth;

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
            'name' => 'История операций',
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
                'title' => 'Операций на странице',
                'comment' => 'Количество операций отображаемых на одной странице',
                'default' => 16,
            ],
        ];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }

        $this->page['operations'] = $this->getOperations();
    }

    /**
     * @return LengthAwarePaginator
     */
    private function getOperations()
    {
        if (!Auth::getUser()) {
            return null;
        }

        return Auth::getUser()
            ->operations()
            ->paginate((int) $this->property('recordsPerPage', 16));
    }
}
