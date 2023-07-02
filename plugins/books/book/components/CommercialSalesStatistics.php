<?php namespace Books\Book\Components;

use Books\Book\Classes\Traits\AccoutBooksTrait;
use Books\Book\Models\Book;
use Books\Book\Models\SellStatistics;
use Books\Book\Traits\FormatNumberTrait;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Db;
use Flash;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * CommercialSalesStatistics Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class CommercialSalesStatistics extends ComponentBase
{
    use FormatNumberTrait,
        AccoutBooksTrait;

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
    }

    public function onRender()
    {
        $this->page['books'] = $this->getAccountBooks();
        $this->page['from'] = $this->from->format('d.m.Y');
        $this->page['to'] = $this->to->format('d.m.Y');
    }

    /**
     * @return array
     */
    public function onFilterStatistics(): array
    {
        $bookId = post('book_id');
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

        if (!empty($bookId)) {
            $book = Book::findOrFail($bookId);
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
        $groupedByDay = $sellStatistics->groupBy('day')->sortBy(fn($group) => $group->first()->sell_at);

        $statisticsData = [];
        foreach ($groupedByDay as $day => $group) {
            $group->each(function ($sell) use (&$statisticsData, $day) {
                $book = $sell->edition->book;
                $statisticsData[$day][] = [
                    'id' => $sell->id,
                    'date' => $sell->day,
                    'sell_type' => $sell->sell_type->value,
                    'book_id' => $book->id,
                    'title' => $book->title,
                    'type' => $sell->sell_type->getLabel(),
                    'price' => $this->formatNumber($sell->price),
                    'count' => 1,
                    'reward' => $this->formatNumber($sell->reward_value),
                ];
            });

            /**
             * Collapse items with same [title, type, price]
             */
            foreach ($statisticsData[$day] as $keyI => $itemI) {
                foreach ($statisticsData[$day] as &$itemJ) {
                    if (
                        $itemI['id'] !== $itemJ['id']
                        && $itemI['book_id'] === $itemJ['book_id']
                        && $itemI['type'] === $itemJ['type']
                        && $itemI['price'] === $itemJ['price']
                    ) {
                        $itemJ['reward'] = $this->formatNumber((int)$itemJ['reward'] + (int)$itemI['reward']);
                        $itemJ['count'] ++;

                        unset($statisticsData[$day][$keyI]);
                    }
                }
            }
        }

        $summary = [
            'sells_count' => $sellStatistics->count(),
            'sells_sum_amount' => $this->formatNumber($sellStatistics->sum('price')),
            'sells_reward_amount' => $this->formatNumber($sellStatistics->sum('reward_value')),
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
        $sellAtRange = SellStatistics
            ::whereIn('profile_id', $this->user->profiles->pluck('id'))
            ->select(DB::raw('MIN(sell_at) AS start_year, MAX(sell_at) AS end_year'))
            ->first();

        if ($sellAtRange->start_year === null || $sellAtRange->end_year === null) {
            $this->from = Carbon::now()->startOfYear();
            $this->to = Carbon::now()->endOfYear();
        } else {
            $this->from = Carbon::createFromFormat('Y-m-d H:i:s', $sellAtRange->start_year);
            $this->to = Carbon::createFromFormat('Y-m-d H:i:s', $sellAtRange->end_year);
        }
    }
}
