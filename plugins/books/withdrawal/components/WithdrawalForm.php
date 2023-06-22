<?php namespace Books\Withdrawal\Components;

use Books\Withdrawal\Classes\Enums\EmploymentTypeEnum;
use Books\Withdrawal\Models\WithdrawalData;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * WithdrawalForm Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class WithdrawalForm extends ComponentBase
{
    protected ?User $user;

    public function componentDetails()
    {
        return [
            'name' => 'WithdrawalForm Component',
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
        $this->page['withdrawal'] = $this->getWithdrawal();
        $this->page['employmentTypes'] = EmploymentTypeEnum::cases();
    }

    /**
     * @return WithdrawalData|null
     */
    private function getWithdrawal(): ?WithdrawalData
    {
        return WithdrawalData
            ::where('profile_id', $this->user->profile->id)
            ->first();
    }

    public function onSaveWithdrawal()
    {
        dd(post());
    }
}
