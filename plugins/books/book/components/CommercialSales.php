<?php namespace Books\Book\Components;

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

    private function getEditionsInSale(): Collection
    {
        $authorships = $this->user->profile
            ->authorships()
            ->with(['book' => fn($q) => $q->defaultEager(), 'book.editions'])
            ->get()
            ->sortByDesc('sort_order');

        $editionsInSale = new Collection();
        $authorships->map(function ($authorship) use (&$editionsInSale) {
            return $editionsInSale->push(
                ...$authorship->book->editions()
                    ->selling()
                    ->get()
            );
        });

        return $editionsInSale;
    }
}
