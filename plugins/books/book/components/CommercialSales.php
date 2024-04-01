<?php namespace Books\Book\Components;

use Books\Book\Classes\Traits\AccoutBooksTrait;
use Books\Book\Models\Edition;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
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

        $this->registerBreadcrumbs();
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

        $editionsInSale = Edition::query()
            ->withPriceEager()
            ->whereHas('book', function($q) use ($accountBooks) {
                $q->whereIn('id', $accountBooks->pluck('id'));
            })
            ->selling()
            ->orderBySalesAt()
            ->get();

        return $editionsInSale;
    }

    /**
     * @return void
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('lc-commercial', static function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('commercial_cabinet');
        });
    }
}
