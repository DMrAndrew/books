<?php namespace Books\Withdrawal\Models;

use Books\Withdrawal\Classes\Contracts\AgreementServiceContract;
use Books\Withdrawal\Classes\Enums\EmploymentTypeEnum;
use Books\Withdrawal\Classes\Enums\WithdrawalAgreementStatusEnum;
use Books\Withdrawal\Classes\Enums\WithdrawalStatusEnum;
use Carbon\Carbon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Model;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;
use System\Models\File;

/**
 * WithdrawalData Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class WithdrawalData extends Model
{
    use Validation;

    const VERIFICATION_CODE_LENGTH = 6;

    public $table = 'books_withdrawal_data';

    public $rules = [
        'user_id' => 'required|exists:users,id',

        'agreement_status' => 'required',
        'withdrawal_status' => 'required',
        'withdraw_frozen' => 'sometimes|boolean',

        'fio' => 'required|string',
        'email' => 'required|email',
        'birthday' => 'required',

        'passport_number' => 'required|string',
        'passport_date' => 'required',
        'passport_issued_by' => 'required',
        'address' => 'required',

        'inn' => 'required|min:9|max:12',
        'employment_type' => 'required',
        'employment_register_number' => 'required_if:employment_type,enterpreneur', //ОГРНИП

        'bank_beneficiary' => 'required', // Банк получатель
        'bank_inn' => 'required',
        'bank_kpp' => 'required',
        'bank_receiver' => 'required', // Получатель
        'bank_account' => 'required',
        'bank_bik' => 'required',
        'bank_corr_account' => 'required',
        //'files' => 'sometimes|array',
        //'files.*' => 'mimes:gif,jpg,jpeg,png|max:3072',
        'approve_code' => 'nullable|string',
    ];

    public $customMessages = [
        'fio.*' => 'Поле `ФИО` обязательно для заполнения',
        'email.required' => 'Поле `Email` обязательно для заполнения',
        'email.email' => 'Поле `Email` должно содержать валидный адрес электронной почты',
        'birthday.required' => 'Поле `Дата рождения` обязательно для заполнения',
        'passport_number.*' => 'Поле `Серия и номер паспорта` обязательно для заполнения',
        'passport_date.required' => 'Поле `Дата выдачи паспорта` обязательно для заполнения',
        'passport_issued_by.required' => 'Поле `Кем выдан паспорт` обязательно для заполнения',
        'address.required' => 'Поле `Адрес регистрации` обязательно для заполнения',
        'inn.required' => 'Поле `ИНН` обязательно для заполнения',
        'inn.min' => 'Поле `ИНН` должно содержать от 9 до 12 символов',
        'inn.max' => 'Поле `ИНН` должно содержать от 9 до 12 символов',
        'employment_type.required' => 'Поле `Тип занятости` обязательно для заполнения',
        'employment_register_number.*' => 'Поле `ОГРНИП` обязательно для типа занятости ИП', //ОГРНИП
        'bank_beneficiary.required' => 'Поле `Банк-получатель` обязательно для заполнения', // Банк получатель
        'bank_inn.required' => 'Поле `ИНН Банка` обязательно для заполнения',
        'bank_inn.numeric' => '`ИНН Банка` должно быть числом',
        'bank_kpp.required' => 'Поле `КПП Банка` обязательно для заполнения',
        'bank_receiver.required' => 'Поле `Получатель` обязательно для заполнения', // Получатель
        'bank_account.required' => 'Поле `Номер счета` обязательно для заполнения',
        'bank_bik.required' => 'Поле `БИК Банка` обязательно для заполнения',
        'bank_corr_account.required' => 'Поле `Корр.счет` обязательно для заполнения',
    ];

    public $fillable = [
        'user_id',
        'agreement_status',
        'withdrawal_status',
        'withdraw_frozen',
        'fio',
        'email',
        'birthday',
        'passport_number',
        'passport_date',
        'passport_issued_by',
        'address',
        'inn',
        'employment_type',
        'employment_register_number',
        'bank_beneficiary',
        'bank_inn',
        'bank_kpp',
        'bank_receiver',
        'bank_account',
        'bank_bik',
        'bank_corr_account',
        'approved_at',
        'files',
        'approve_code',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'approved_at',
        'birthday',
        'passport_date',
    ];

    protected $casts = [
        'agreement_status' => WithdrawalAgreementStatusEnum::class,
        'withdrawal_status' => WithdrawalStatusEnum::class,
        'employment_type' => EmploymentTypeEnum::class,
    ];

    /**
     * @var array
     */
    public $belongsTo = [
        'user' => [User::class],
    ];

    public $attachMany = [
        'files' => File::class,
    ];

    /**
     * @return string
     * @throws BindingResolutionException
     */
    public function agreementHTML(): string
    {
        $agreementService = app()->make(AgreementServiceContract::class, ['user' => $this->user]);

        return $agreementService->getAgreementHTML();
    }

    /**
     * @return string
     */
    public static function generateCode(): string
    {
        return substr(
            strtoupper(hash('xxh32', Carbon::now()->toISOString())),
            0, self::VERIFICATION_CODE_LENGTH);
    }

    /**
     * @return array
     */
    public function getAgreementStatusOptions(): array
    {
        $options = [];
        foreach (WithdrawalAgreementStatusEnum::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        };

        return $options;
    }

    /**
     * @return array
     */
    public function getWithdrawalStatusOptions(): array
    {
        $options = [];
        foreach (WithdrawalStatusEnum::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        };

        return $options;
    }

    /**
     * @return array
     */
    public function getEmploymentTypeOptions(): array
    {
        $options = [];
        foreach (EmploymentTypeEnum::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        };

        return $options;
    }

    /**
     * @return string|null
     */
    public function getAgreementStatusNameAttribute(): ?string
    {
        return $this->agreement_status?->getLabel();
    }

    /**
     * @return string|null
     */
    public function getWithdrawalStatusNameAttribute(): ?string
    {
        return $this->withdrawal_status?->getLabel();
    }
}
