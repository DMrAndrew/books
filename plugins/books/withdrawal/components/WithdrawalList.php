<?php namespace Books\Withdrawal\Components;

use Books\Withdrawal\Models\Withdrawal;
use Books\Withdrawal\Models\WithdrawalData;
use Cms\Classes\ComponentBase;
use Illuminate\Pagination\LengthAwarePaginator;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * WithdrawalList Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class WithdrawalList extends ComponentBase
{
    protected ?User $user;

    public function componentDetails()
    {
        return [
            'name' => 'WithdrawalList Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [
            'recordsPerPage' => [
                'title' => 'Операций вывода на странице',
                'comment' => 'Количество операций вывода отображаемых на одной странице',
                'default' => 16,
            ],
        ];
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
        $this->page['approvedAgreementExist'] = $this->isApprovedAgreementExist();
        $this->page['withdrawals'] = $this->getWithdrawals();
    }

    /**
     * @return bool
     */
    private function isApprovedAgreementExist(): bool
    {
        return (bool) WithdrawalData
            ::where('user_id', $this->user->id)
            ->approved()
            ->first();
    }

    /**
     * @return LengthAwarePaginator
     */
    private function getWithdrawals(): LengthAwarePaginator
    {
        return Withdrawal
            ::where('user_id', $this->user->id)
            ->paginate((int) $this->property('recordsPerPage', 16));
    }
}
