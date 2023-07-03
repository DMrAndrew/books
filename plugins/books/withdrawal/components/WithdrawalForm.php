<?php namespace Books\Withdrawal\Components;

use Books\FileUploader\Components\ImageUploader;
use Books\Withdrawal\Classes\Contracts\AgreementServiceContract;
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
use Redirect;
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
    private ?WithdrawalData $withdrawal;

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
        $this->withdrawal = $this->getWithdrawal();

        $component = $this->addComponent(
            ImageUploader::class,
            'fileUploader',
            [
                'modelClass' => WithdrawalData::class,
                'deferredBinding' => !(bool)$this->withdrawal?->id,
                "placeholderText" => "Скан паспорта с пропиской (данные паспорта не хранятся и удаляются сразу после проверки)",
                "maxSize" => 30,
                "isMulti" => true,
                "fileTypes" => ".gif,.jpg,.jpeg,.png",
            ]
        );

        $component->bindModel('files', $this->withdrawal);
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
            ::where('user_id', $this->user->id)
            ->firstOrNew();
    }

    public function onSaveWithdrawal()
    {
        try {
            $formData = array_merge(post(), [
                'user_id' => $this->user->id,
                'agreement_status' => WithdrawalAgreementStatusEnum::SIGNING,
                'withdrawal_status' => WithdrawalStatusEnum::ALLOWED,
                'birthday' => post('birthday')
                    ? Carbon::createFromFormat('d.m.Y', post('birthday'))
                    : null,
                'passport_date' => post('passport_date')
                    ? Carbon::createFromFormat('d.m.Y', post('passport_date'))
                    : null,
            ]);

            $validator = Validator::make(
                $formData,
                collect((new WithdrawalData())->rules)->toArray()
            );
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            WithdrawalData::updateOrCreate([
                'user_id' => $this->user->id,
            ], $formData);

        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
            return [];
        }

        return Redirect::refresh();
    }

    public function onSetFillingStatus()
    {
        try {
            $this->withdrawal->update([
                'agreement_status' => WithdrawalAgreementStatusEnum::FILLING,
            ]);
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
            return [];
        }

        return Redirect::refresh();
    }

    public function onSendVerificationCode()
    {
        try {
            $agreementService = app()->make(AgreementServiceContract::class, ['user' => $this->user]);
            $agreementService->sendVerificationCode();

            $this->withdrawal->update([
                'agreement_status' => WithdrawalAgreementStatusEnum::VERIFYING,
            ]);
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
            return [];
        }

        return [
            '#verification_input' => $this->renderPartial('withdrawal/verify')
        ];
    }

    public function onVerify()
    {
        dd(post());
    }
}
