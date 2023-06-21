<?php namespace Books\Book\Components;

use Books\Book\Models\SellStatistics;
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
    use FormatNumberTrait;

    protected ?User $user;
    private int $book_id;
    private string $date;

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
        $this->date = (string)$this->param('date');
    }

    public function onRender()
    {
        /**
         * Book
         */
        $book = $this->user->profile->books()->findOrFail($this->book_id);
        $editionIds = $book->editions->pluck('id')->toArray();
        $this->page['book'] = $book;

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
            ->where('profile_id', $this->user->profile->id)
            ->whereBetween('sell_at', [$periodFrom, $periodTo])
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
            $statisticsData[$type][] = [
                'type' => $type,
                'count' => $group->count(),
                'price' => $this->formatNumber($group->first()?->price),
                'amount' => $this->formatNumber($group->sum('price')),
                'tax_rate' => $group->first()?->tax_rate . '%',
                'tax_value' => $this->formatNumber($group->sum('tax_value')),
                'reward' => $this->formatNumber($group->sum('reward_value')),
            ];
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
}
