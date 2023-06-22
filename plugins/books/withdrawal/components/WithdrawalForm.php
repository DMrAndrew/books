<?php namespace Books\Withdrawal\Components;

use Books\Withdrawal\Classes\Enums\EmploymentTypeEnum;
use Books\Withdrawal\Classes\Enums\WithdrawalAgreementStatusEnum;
use Books\Withdrawal\Classes\Enums\WithdrawalStatusEnum;
use Books\Withdrawal\Models\WithdrawalData;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use ValidationException;
use Validator;

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
        try {
            $formData = array_merge(post(), [
                'profile_id' => $this->user->profile->id,
                'agreement_status' => WithdrawalAgreementStatusEnum::SIGNING,
                'withdrawal_status' => WithdrawalStatusEnum::ALLOWED,
                //'birthday' => Carbon::createFromFormat('d.m.Y', post('birthday')),
                //'passport_date' => Carbon::createFromFormat('d.m.Y', post('passport_date')),
            ]);

            $validator = Validator::make(
                $formData,
                collect((new WithdrawalData())->rules)->toArray()
            );
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            WithdrawalData::updateOrCreate([
                'profile_id' => $this->user->profile->id,
            ], $formData);

        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
            return [];
        }
    }
}
