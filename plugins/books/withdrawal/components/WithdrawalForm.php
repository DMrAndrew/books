<?php namespace Books\Withdrawal\Components;

use Books\Book\Traits\FormatNumberTrait;
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
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\RedirectResponse;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;
use Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ValidationException;
use Validator;

/**
 * WithdrawalForm Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class WithdrawalForm extends ComponentBase
{
    use FormatNumberTrait;

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
        return [
            'paramCode' => [
                'title'       => 'Код подтверждения договора на вывод средств',
                'description' => 'Параметр страницы, используемые для подтверждения договора на вывод средств',
                'type'        => 'string',
                'default'     => 'code'
            ],
        ];
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

    public function onRun()
    {
        /*
         * Activation code supplied
         */
        if ($code = $this->verificationCode()) {
            $this->onVerifyCode($code);
        }
    }

    public function onRender()
    {
        $this->page['user'] = $this->user;
        $this->page['withdrawal'] = $this->getWithdrawal();
        $this->page['withdraw_available'] = $this->formatNumber($this->user->proxyWallet()->balance);
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

    /**
     * @return array|RedirectResponse
     */
    public function onSaveWithdrawal(): array|RedirectResponse
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
                'employment_register_number' => post('employment_type') == EmploymentTypeEnum::ENTERPRENEUR->value
                    ? post('employment_register_number')
                    : null,
            ]);

            $validator = Validator::make(
                $formData,
                collect((new WithdrawalData())->rules)->toArray(),
                collect((new WithdrawalData())->customMessages)->toArray(),
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

    /**
     * @return array|RedirectResponse
     */
    public function onSetFillingStatus(): array|RedirectResponse
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

    /**
     * @return array|RedirectResponse
     */
    public function onSendVerificationCode(): array|RedirectResponse
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

    /**
     * @param null $code
     *
     * @return array|RedirectResponse
     * @throws BindingResolutionException
     */
    public function onVerifyCode($code = null): array|RedirectResponse
    {
        $verificationCode = post('verification_code', $code);

        if ($verificationCode == null) {
            Flash::error('Необходимо ввести код подтверждения');
            return [];
        }

        if ($this->withdrawal->agreement_status == WithdrawalAgreementStatusEnum::CHECKING) {
            return Redirect::to('/lc-commercial-withdraw');
        }

        $agreementService = app()->make(AgreementServiceContract::class, ['user' => $this->user]);
        if ($agreementService->verifyAgreement($verificationCode)) {

            return Redirect::refresh();
        } else {
            Flash::error('Неверный код подтверждения');

            return [];
        }
    }

    /**
     * @return string|null
     */
    private function verificationCode(): ?string
    {
        $routeParameter = $this->property('paramCode');

        if ($code = $this->param($routeParameter)) {
            return $code;
        }

        return get('verification_code');
    }

    /**=
     * @return array|RedirectResponse
     */
    public function onFreezeWithdrawal(): array|RedirectResponse
    {
        $freeze = (bool) post('freeze', true);

        try {
            $this->withdrawal->update([
                'withdraw_frozen' => $freeze,
            ]);
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());

            return [];
        }

        return Redirect::refresh();
    }

    /**
     * @return array|BinaryFileResponse
     * @throws BindingResolutionException
     */
    public function onDownloadAgreement(): array|BinaryFileResponse
    {
        $agreementService = app()->make(AgreementServiceContract::class, ['user' => $this->user]);
        try {
            $pdf = $agreementService->getAgreementPDF();
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());

            return [];
        }

        return Response::download(
            $pdf,
            null,
            [
                'Content-Type' => 'application/pdf'
            ]
        );
    }

    /**
     * @return array
     */
    public function onSelectEmploymentType(): array
    {
        $employmentType = post('value', '');
        if ($employmentType == EmploymentTypeEnum::ENTERPRENEUR->value) {
            return [
                '#ogrnip-container' => $this->renderPartial('@field_ogrnip', ['withdrawal' => $this->withdrawal]),
            ];
        }

        return [
            '#ogrnip-container' => '',
        ];
    }
}
