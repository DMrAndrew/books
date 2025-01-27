<?php namespace Books\Referral\Components;

use Books\Referral\Models\ReferralStatistics;
use Books\Referral\Models\ReferralVisit;
use Books\Referral\Models\Referrer;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Db;
use Flash;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * LCReferrer Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class LCReferralStatistics extends ComponentBase
{
    protected ?User $user;

    protected Carbon $from;

    protected Carbon $to;

    public function componentDetails()
    {
        return [
            'name' => 'LCReferralStatistics Component',
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

        $this->prepareStatisticDates();
    }

    public function onRender()
    {
        $this->page['user'] = $this->user;
        $this->page['referrer'] = $this->getReferrer();
        $this->page['from'] = $this->from->format('d.m.Y');
        $this->page['to'] = $this->to->format('d.m.Y');
    }

    /**
     * @return Referrer|null
     */
    private function getReferrer(): ?Referrer
    {
        return $this->user->referrer;
    }

    /**
     * @return array
     */
    public function onFilterStatistics(): array
    {
        $period = post('dates');

        if (empty($period)) {
            Flash::error('Необходимо выбрать Период для построения отчета');

            return[];
        }

        [$dateFrom, $dateTo] = explode(' - ', $period);
        $periodFrom = Carbon::createFromFormat('d.m.Y', $dateFrom);
        $periodTo = Carbon::createFromFormat('d.m.Y', $dateTo);

        /**
         * Limit select range to 3 months
         */
        $periodStartFrom = $periodTo->copy()->subMonths(3);
        if ($periodFrom->lessThan($periodStartFrom)) {
            $periodFrom = $periodStartFrom;
            Flash::warning('Максимальный период для построения отчета составляет 3 месяца. Показаны последние 3 месяца выбранного периода');
        }

        /**
         * Dates from visits and sell statistic
         */
        $referrerIds = $this->user->referrers->pluck('id')->toArray();

        $datesByVisits = ReferralVisit
            ::whereIn('referrer_id', $referrerIds)
            ->whereDate('created_at', '>=', $periodFrom)
            ->whereDate('created_at', '<=', $periodTo)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%d.%m.%Y') as `day`"),
                DB::raw("`created_at` as `sort_time`"),
            );

        $datesBySellStatistics = ReferralStatistics
            ::whereIn('referrer_id', $referrerIds)
            ->whereDate('sell_at', '>=', $periodFrom)
            ->whereDate('sell_at', '<=', $periodTo)
            ->select(
                DB::raw("DATE_FORMAT(sell_at, '%d.%m.%Y') as `day`"),
                DB::raw("`sell_at` as `sort_time`"),
            );

        $unionDates = $datesBySellStatistics
            ->union($datesByVisits)
            ->orderByDesc('sort_time')
            ->get();

        $days = $unionDates->pluck('day')->unique()->toArray();

        $statisticsData = [];
        foreach ($days as $day) {
            $statisticsData[$day][] = [
                'date' => $day,
                'sells_count' => 0,
                'visits_count' => 0,
                'reward' => formatMoneyAmount(0),
            ];
        }

        /**
         * Sales stats
         */
        $referralStatistics = ReferralStatistics
            ::where('user_id', $this->user->id)
            ->whereDate('sell_at', '>=', $periodFrom)
            ->whereDate('sell_at', '<=', $periodTo)
            ->orderBy('sell_at', 'desc')
            ->orderBy('price', 'desc')
            ->get();

        /**
         * Format `sell_at` date to group key
         */
        $referralStatistics->each(function ($sell) {
            $sell->day = $sell->sell_at->format('d.m.Y');
        });
        $groupedByDay = $referralStatistics->groupBy('day')->sortByDesc(fn($group) => $group->first()->sell_at);

        $sellStatisticsData = [];
        foreach ($groupedByDay as $day => $group) {
            $group->each(function ($sell) use (&$sellStatisticsData, $day) {
                $sellStatisticsData[$day][] = [
                    'id' => $sell->id,
                    'date' => $sell->day,
                    'sells_count' => 1,
                    'visits_count' => 0,
                    'reward' => formatMoneyAmount($sell->reward_value),
                ];
            });

            /**
             * Collapse items with same [partner code, etc]
             */
            foreach ($sellStatisticsData[$day] as $keyI => $itemI) {
                foreach ($sellStatisticsData[$day] as &$itemJ) {
                    if (
                        $itemI['id'] !== $itemJ['id']
                    ) {
                        $itemJ['reward'] = formatMoneyAmount((int)$itemJ['reward'] + (int)$itemI['reward']);
                        $itemJ['sells_count'] ++;

                        unset($sellStatisticsData[$day][$keyI]);
                    }
                }
            }
        }

        foreach ($statisticsData as $day => &$statisticsDay) {
            if (isset($sellStatisticsData[$day])) {
                $statisticsDay = $sellStatisticsData[$day];
            }
        }

        /**
         * Visits stats
         */
        $referralVisits = ReferralVisit
            ::whereIn('referrer_id', $referrerIds)
            ->whereDate('created_at', '>=', $periodFrom)
            ->whereDate('created_at', '<=', $periodTo)
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%d.%m.%Y')"))
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%d.%m.%Y') as `day`"),
                DB::raw("count(*) as `visits_count`"),
            )
            ->get();

        $referralVisitsByDays = $referralVisits->pluck('visits_count', 'day')->toArray();

        foreach ($referralVisitsByDays as $day => $visitsCount) {
            if (isset($statisticsData[$day])) {
                foreach($statisticsData[$day] as &$record) {
                    $record['visits_count'] = $visitsCount;
                }
            }
        }

        /**
         * Table summary
         */
        $summary = [
            'sells_count' => $referralStatistics->count(),
            'sells_reward_amount' => formatMoneyAmount($referralStatistics->sum('reward_value')),
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
    private function prepareStatisticDates()
    {
        /*
        // статистика на основании имеющихся продаж
        $sellAtRange = ReferralStatistics
            ::where('user_id', $this->user->id)
            ->select(DB::raw('MIN(sell_at) AS start_year, MAX(sell_at) AS end_year'))
            ->first();

        if ($sellAtRange->start_year === null || $sellAtRange->end_year === null) {
            $this->from = Carbon::now()->startOfYear();
            $this->to = Carbon::now()->endOfYear();
        } else {
            $this->from = Carbon::createFromFormat('Y-m-d H:i:s', $sellAtRange->start_year);
            $this->to = Carbon::createFromFormat('Y-m-d H:i:s', $sellAtRange->end_year);
        }
        */

        // последний месяц
        $this->to = Carbon::now()->endOfDay();
        $this->from = Carbon::now()->subMonth()->endOfDay();
    }
}
