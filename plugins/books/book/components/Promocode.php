<?php namespace Books\Book\Components;

use Books\Book\Models\Promocode as PromocodeModel;
use Cms\Classes\ComponentBase;
use Illuminate\Database\Eloquent\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Promocode Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Promocode extends ComponentBase
{
    protected User $user;

    public function componentDetails()
    {
        return [
            'name' => 'Promocode Component',
            'description' => 'Компонент промокодов'
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
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser();

        $this->page['promocodes'] = $this->getBooksPromocodes();
        $this->page['books'] = $this->user->profile->books()->get();
    }

    public function generate()
    {

    }

    public function activate()
    {
        // todo реализовать после возможности оплаты/покупки
    }

    public function getBooksPromocodes(): Collection
    {
        $promocodes = PromocodeModel
            ::with(['user'])
            ->get();
        //dd($promocodes);
        return PromocodeModel::get();
    }
}
