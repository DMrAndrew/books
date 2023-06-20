<?php namespace Books\Book\Components;

use Books\Book\Models\Book;
use Books\Book\Models\SellStatistics;
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
        $this->page['books'] = $this->user->profile->books()->get();
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

        if (empty($bookId) || empty($period)) {
            Flash::error('Необходимо выбрать Произведение и Период для построения отчета');

            return[];
        }

        [$dateFrom, $dateTo] = explode(' - ', $period);
        $periodFrom = Carbon::createFromFormat('d.m.Y', $dateFrom);
        $periodTo = Carbon::createFromFormat('d.m.Y', $dateTo);

        $book = Book::findOrFail($bookId);
        $editionIds = $book->editions->pluck('id')->toArray();

        $sellStatistics = SellStatistics
            ::with(['edition', 'edition.book'])
            ->where('profile_id', $this->user->profile->id)
            ->whereBetween('sell_at', [$periodFrom, $periodTo])
            ->whereIn('edition_id', $editionIds)
            ->orderBy('sell_at', 'desc')
            ->get();

        /**
         * Format `sell_at` date to group key
         */
        $sellStatistics->each(function ($sell) {
            $sell->day = $sell->sell_at->format('d.m.Y');
        });
        $groupedByDay = $sellStatistics->groupBy('day');

        $statisticsData = [];
        foreach ($groupedByDay as $day => $group) {
            $group->each(function ($sell) use (&$statisticsData, $day) {
                $book = $sell->edition->book;
                $statisticsData[$day][] = [
                    'id' => $sell->id,
                    'date' => $sell->day,
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
                        && $itemI['title'] === $itemJ['title']
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
            ::where('profile_id', $this->user->profile->id)
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

    /**
     * @param $number
     *
     * @return string
     */
    private function formatNumber($number): string
    {
        return number_format($number, 2, '.', '');
    }
}
