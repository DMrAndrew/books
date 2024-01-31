<?php namespace Books\Book\Components;

use Books\Book\Models\Book;
use Books\Book\Models\Edition;
use Books\Book\Models\SellStatistics;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Flash;
use Illuminate\Database\Eloquent\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * CommercialSalesStatistics Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class CommercialSalesStatistics extends ComponentBase
{
    protected ?User $user;

    protected Carbon $from;

    protected Carbon $to;

    public function componentDetails()
    {
        return [
            'name' => 'CommercialSalesStatistics Component',
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

        $this->prepareDates();

        $this->registerBreadcrumbs();
    }

    public function onRender()
    {
        $this->page['editions'] = $this->getAccountEditions();
        $this->page['from'] = $this->from->format('d.m.Y');
        $this->page['to'] = $this->to->format('d.m.Y');
    }

    /**
     * @return array
     */
    public function onFilterStatistics(): array
    {
        $editionId = post('edition_id');
        $period = post('dates');

        if (empty($period)) {
            Flash::error('Необходимо выбрать Период для построения отчета');

            return[];
        }

        [$dateFrom, $dateTo] = explode(' - ', $period);
        $periodFrom = Carbon::createFromFormat('d.m.Y', $dateFrom);
        $periodTo = Carbon::createFromFormat('d.m.Y', $dateTo);

        $sellStatisticsQuery = SellStatistics
            ::with(['edition', 'edition.book'])
            ->whereIn('profile_id', $this->user->profiles->pluck('id'))
            ->whereDate('sell_at', '>=', $periodFrom)
            ->whereDate('sell_at', '<=', $periodTo);

        if (! empty($editionId)) {

            $book = Book
                ::whereHas('editions', function ($q) use ($editionId) {
                    $q->where('id', $editionId);
                })
                ->whereHas('authors', function ($q) use ($editionId) {
                    $q->whereIn('profile_id', $this->user->profiles->pluck('id'));
                })
                ->first();

            $editionIds = $book->editions->pluck('id')->toArray();
            $sellStatisticsQuery->whereIn('edition_id', $editionIds);
        }

        $sellStatistics = $sellStatisticsQuery
            ->orderBy('edition_id', 'desc')
            ->orderBy('sell_type', 'desc')
            ->orderBy('price', 'desc')
            ->get();

        /**
         * Format `sell_at` date to group key
         */
        $sellStatistics->each(function ($sell) {
            $sell->day = $sell->sell_at->format('d.m.Y');
        });
        $groupedByDay = $sellStatistics->groupBy('day')->sortByDesc(fn($group) => $group->first()->sell_at);

        $statisticsData = [];
        foreach ($groupedByDay as $day => $group) {
            $group->each(function ($sell) use (&$statisticsData, $day) {
                $edition = $sell->edition;
                $book = $sell->edition->book;
                $statisticsData[$day][] = [
                    'id' => $sell->id,
                    'date' => $sell->day,
                    'sell_type' => $sell->sell_type->value,
                    'book_id' => $book->id,
                    'edition_id' => $edition->id,
                    'title' => $edition->title,
                    'type' => $sell->sell_type->getLabel(),
                    'price' => $sell->price,
                    'count' => 1,
                    'reward' => $sell->reward_value,
                ];
            });

            /**
             * Collapse items with same [title, type, price]
             */
            foreach ($statisticsData[$day] as $keyI => $itemI) {
                foreach ($statisticsData[$day] as &$itemJ) {
                    if (
                        $itemI['id'] !== $itemJ['id']
                        && $itemI['edition_id'] === $itemJ['edition_id']
                        && $itemI['type'] === $itemJ['type']
                        && $itemI['price'] === $itemJ['price']
                    ) {
                        $itemJ['reward'] = (int)$itemJ['reward'] + (int)$itemI['reward'];
                        $itemJ['count'] ++;

                        unset($statisticsData[$day][$keyI]);
                    }
                }
            }
        }

        $summary = [
            'sells_count' => $sellStatistics->count(),
            'sells_sum_amount' => $sellStatistics->sum('price'),
            'sells_reward_amount' => $sellStatistics->sum('reward_value'),
        ];

        return [
            '#statisticsDataTable' => $this->renderPartial('@statistics-table', [
                'data' => $statisticsData,
                'summary' => $summary,
            ]),
        ];

    }

    /**
     * @return void
     */
    private function prepareDates()
    {
        $this->from = Carbon::now()->startOfMonth();
        $this->to = Carbon::now()->endOfMonth();
        return;
//        $sellAtRange = SellStatistics
//            ::whereIn('profile_id', $this->user->profiles->pluck('id'))
//            ->select(DB::raw('MIN(sell_at) AS start_year, MAX(sell_at) AS end_year'))
//            ->first();
//
//        if ($sellAtRange->start_year === null || $sellAtRange->end_year === null) {
//            $this->from = Carbon::now()->startOfYear();
//            $this->to = Carbon::now()->endOfYear();
//        } else {
//            $this->from = Carbon::createFromFormat('Y-m-d H:i:s', $sellAtRange->start_year);
//            $this->to = Carbon::createFromFormat('Y-m-d H:i:s', $sellAtRange->end_year);
//        }
    }

    /**
     * @return void
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('lc-commercial-statistics', static function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('commercial_cabinet');
            $trail->push('Статистика продаж');
        });
    }

    /**
     * @return Collection
     */
    private function getAccountEditions(): Collection
    {
        $books = $this->user->toBookUser()->booksInAuthorOrder()->get();

        return Edition::query()
            ->whereIn('book_id', $books->pluck('id')->toArray())
            ->get();
    }
}
