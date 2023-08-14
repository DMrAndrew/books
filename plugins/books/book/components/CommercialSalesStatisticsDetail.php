<?php namespace Books\Book\Components;

use Books\Book\Classes\Enums\SellStatisticSellTypeEnum;
use Books\Book\Classes\Traits\AccoutBooksTrait;
use Books\Book\Models\Book;
use Books\Book\Models\SellStatistics;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Books\Book\Traits\FormatNumberTrait;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * CommercialSalesStatisticsDetail Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class CommercialSalesStatisticsDetail extends ComponentBase
{
    use FormatNumberTrait,
        AccoutBooksTrait;

    protected ?User $user;
    private int $book_id;
    private ?Book $book;
    private string $date;
    private string $sell_type;

    public function componentDetails()
    {
        return [
            'name' => 'CommercialSalesStatisticsDetail Component',
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

        $this->book_id = (int)$this->param('book_id');
        $this->book = $this->getAccountBooks()->where('id', $this->book_id)->first();
        $this->date = (string)$this->param('date');
        $this->sell_type = (string)$this->param('sell_type');

        $this->registerBreadcrumbs();
    }

    public function onRender()
    {
        $this->page['sell_type'] = $this->getSellType();

        /**
         * Book
         */
        if (!$this->book) {
            abort(404);
        }

        $editionIds = $this->book->editions->pluck('id')->toArray();
        $this->page['book'] = $this->book;

        /**
         * Report date
         */
        $dateCarbon = Carbon::createFromFormat('d.m.Y', $this->date);
        $periodFrom = $dateCarbon->copy()->startOfDay();
        $periodTo = $dateCarbon->copy()->endOfDay();
        $this->page['date'] = $dateCarbon->format('d.m.Y');

        /**
         * Статистика продаж
         */
        $sellStatistics = SellStatistics
            ::with(['edition', 'edition.book'])
            ->where('sell_type', $this->sell_type)
            ->whereIn('profile_id', $this->user->profiles->pluck('id'))
            ->whereDate('sell_at', '>=', $periodFrom)
            ->whereDate('sell_at', '<=', $periodTo)
            ->whereIn('edition_id', $editionIds)
            ->get();

        /**
         * For valid group key
         */
        $sellStatistics->each(function ($sell) {
            $sell->edition_type_label = (string) $sell->edition_type->label();
        });
        $groupedByEdition = $sellStatistics->groupBy('edition_type_label');

        $statisticsData = [];
        foreach ($groupedByEdition as $type => $group) {
            $group->each(function ($sell) use (&$statisticsData, $type) {
                $statisticsData[$type][] = [
                    'id' => $sell->id,
                    'type' => $type,
                    'count' => 1,
                    'price' => $this->formatNumber($sell->price),
                    'amount' => $this->formatNumber($sell->price),
                    'tax_rate' => $sell->tax_rate . '%',
                    'tax_value' => $this->formatNumber($sell->tax_value),
                    'reward' => $this->formatNumber($sell->reward_value),
                ];
            });

            /**
             * Collapse items with same [type, price, tax_rate]
             */
            foreach ($statisticsData[$type] as $keyI => $itemI) {
                foreach ($statisticsData[$type] as &$itemJ) {
                    if (
                        $itemI['id'] !== $itemJ['id']
                        && $itemI['type'] === $itemJ['type']
                        && $itemI['price'] === $itemJ['price']
                        && $itemI['tax_rate'] === $itemJ['tax_rate']
                    ) {
                        $itemJ['amount'] = $this->formatNumber((int)$itemJ['amount'] + (int)$itemI['amount']);
                        $itemJ['tax_value'] = $this->formatNumber((int)$itemJ['tax_value'] + (int)$itemI['tax_value']);
                        $itemJ['reward'] = $this->formatNumber((int)$itemJ['reward'] + (int)$itemI['reward']);
                        $itemJ['count'] ++;

                        unset($statisticsData[$type][$keyI]);
                    }
                }
            }
        }
        $this->page['statistics'] = $statisticsData;

        /**
         * Summary
         */
        $summary = [
            'sells_count' => $sellStatistics->count(),
            'sells_amount_total' => $this->formatNumber($sellStatistics->sum('price')),
            'sells_tax_total' => $this->formatNumber($sellStatistics->sum('tax_value')),
            'sells_reward_total' => $this->formatNumber($sellStatistics->sum('reward_value')),
        ];
        $this->page['summary'] = $summary;
    }

    /**
     * @return string|null
     */
    private function getSellType(): ?string
    {
        foreach(SellStatisticSellTypeEnum::cases() as $case) {
            if ($case->value == $this->sell_type) {
                return $case->getLabel();
            }
        }

        return null;
    }

    /**
     * @return void
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('lc-commercial-statistics-detail', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('commercial_cabinet');
            $trail->push('Статистика продаж', url('/lc-commercial-statistics'));
            if ($this->book) {
                $trail->push($this->book->title);
            }
        });
    }
}
