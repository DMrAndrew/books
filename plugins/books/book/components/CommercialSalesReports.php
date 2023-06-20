<?php namespace Books\Book\Components;

use Books\Book\Classes\Enums\SellStatisticSellTypeEnum;
use Books\Book\Models\SellStatistics;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Db;
use Flash;
use October\Rain\Database\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * CommercialSalesReports Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class CommercialSalesReports extends ComponentBase
{
    protected ?User $user;

    public function componentDetails()
    {
        return [
            'name' => 'CommercialSalesReports Component',
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
        $this->page['sell_years'] = $this->getSellsYears();
    }

    public function onFilterSales()
    {
        $year = post('sells_year');
        $month = post('sells_month');

        if (empty($year) || empty($month)) {
            Flash::error('Необходимо выбрать Год и Месяц для построения отчета');

            return[];
        }

        $periodFrom = Carbon::createFromFormat('Y-m', $year . '-' . $month)->startOfMonth();
        $periodTo = $periodFrom->copy()->endOfMonth();

        $sellStatistics = SellStatistics
            ::with(['edition', 'edition.book'])
            ->where('profile_id', $this->user->profile->id)
            ->whereBetween('sell_at', [$periodFrom, $periodTo])
            ->get();

        /**
         * Доходы от завершенных книг
         */
        $completedEditionsSell = $sellStatistics->filter(function($sell) {
            return $sell->sell_type === SellStatisticSellTypeEnum::SELL;
        });
        $groupedCompleted = $completedEditionsSell->groupBy('edition_id');
        $completedData = [];
        $groupedCompleted->each(function($groupedEdition) use (&$completedData) {
            $book = $groupedEdition->first()->edition->book;
            $completedData[] = [
                'id' => $book->id,
                'title' => $book->title,
                'sells_sum_amount' => $this->formatNumber($groupedEdition->sum('price')),
                'tax_sum_amount' => $this->formatNumber($groupedEdition->sum('tax_value')),
                'reward_sum_amount' => $this->formatNumber($groupedEdition->sum('reward_value')),
            ];
        });

        $completedSummary = [
            'sells_sum_amount' => $this->formatNumber($completedEditionsSell->sum('price')),
            'tax_sum_amount' => $this->formatNumber($completedEditionsSell->sum('tax_value')),
            'reward_sum_amount' => $this->formatNumber($completedEditionsSell->sum('reward_value')),
        ];

        /**
         * Доходы от незавершенных книг
         */
        $workingEditionsSell = $sellStatistics->filter(function($sell) {
            return $sell->sell_type === SellStatisticSellTypeEnum::SUBSCRIBE;
        });
        $groupedIncompleted = $workingEditionsSell->groupBy('edition_id');
        $incompletedData = [];
        $groupedIncompleted->each(function($groupedEdition) use (&$incompletedData) {
            $book = $groupedEdition->first()->edition->book;
            $incompletedData[] = [
                'id' => $book->id,
                'title' => $book->title,
                'sells_sum_amount' => $this->formatNumber($groupedEdition->sum('price')),
                'tax_sum_amount' => $this->formatNumber($groupedEdition->sum('tax_value')),
                'reward_sum_amount' => $this->formatNumber($groupedEdition->sum('reward_value')),
            ];
        });

        $incompletedSummary = [
            'sells_sum_amount' => $this->formatNumber($workingEditionsSell->sum('price')),
            'tax_sum_amount' => $this->formatNumber($workingEditionsSell->sum('tax_value')),
            'reward_sum_amount' => $this->formatNumber($workingEditionsSell->sum('reward_value')),
        ];

        return [
            '#completedDataTable' => $this->renderPartial('@report-table', [
                'data' => $completedData,
                'summary' => $completedSummary,
            ]),
            '#incompletedDataTable' => $this->renderPartial('@report-table', [
                'data' => $incompletedData,
                'summary' => $incompletedSummary,
            ]),
        ];

    }

    /**
     * @return array
     */
    private function getSellsYears(): array
    {
        $sellAtRange = SellStatistics
            ::where('profile_id', $this->user->profile->id)
            ->select(DB::raw('MIN(sell_at) AS start_year, MAX(sell_at) AS end_year'))
            ->first();

        if ($sellAtRange->start_year === null || $sellAtRange->end_year === null) {
            return [];
        }

        $startYear = Carbon::createFromFormat('Y-m-d H:i:s', $sellAtRange->start_year)->year;
        $endYear = Carbon::createFromFormat('Y-m-d H:i:s', $sellAtRange->end_year)->year;

        return range($startYear, $endYear);
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
