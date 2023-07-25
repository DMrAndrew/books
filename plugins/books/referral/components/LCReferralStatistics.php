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

        $referralStatisticsQuery = ReferralStatistics
            ::where('user_id', $this->user->id)
            ->whereDate('sell_at', '>=', $periodFrom)
            ->whereDate('sell_at', '<=', $periodTo);

        $referralStatistics = $referralStatisticsQuery
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

        $statisticsData = [];
        foreach ($groupedByDay as $day => $group) {
            $group->each(function ($sell) use (&$statisticsData, $day) {
                $statisticsData[$day][] = [
                    'id' => $sell->id,
                    'date' => $sell->day,
                    'sells_count' => 1,
                    'visits_count' => 0,
                    'reward' => formatMoneyAmount($sell->reward_value),
                ];
            });

            /**
             * Collapse items with same [title, type, price]
             */
            foreach ($statisticsData[$day] as $keyI => $itemI) {
                foreach ($statisticsData[$day] as &$itemJ) {
                    if (
                        $itemI['id'] !== $itemJ['id']
                    ) {
                        $itemJ['reward'] = formatMoneyAmount((int)$itemJ['reward'] + (int)$itemI['reward']);
                        $itemJ['sells_count'] ++;

                        unset($statisticsData[$day][$keyI]);
                    }
                }
            }
        }

        /**
         * Referral visits count
         */
        $referrerIds = $referralStatistics->pluck('referrer_id')->unique()->toArray();
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
    }
}
