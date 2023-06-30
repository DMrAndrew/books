<?php namespace Books\Book\Components;

use Books\Book\Classes\Traits\AccoutBooksTrait;
use Cms\Classes\ComponentBase;
use October\Rain\Database\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * CommercialSales Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class CommercialSales extends ComponentBase
{
    use AccoutBooksTrait;

    protected ?User $user;

    public function componentDetails()
    {
        return [
            'name' => 'CommercialSales Component',
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
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser();
    }

    public function onRender()
    {
        $this->page['editions'] = $this->getEditionsInSale();
    }

    /**
     * @return Collection
     */
    private function getEditionsInSale(): Collection
    {
        $accountBooks = $this->getAccountBooks();

        $editionsInSale = new Collection();
        $accountBooks->map(function ($book) use (&$editionsInSale) {
            return $editionsInSale->push(
                ...$book->editions()
                    ->selling()
                    ->get()
                );
        });

        return $editionsInSale;
    }
}