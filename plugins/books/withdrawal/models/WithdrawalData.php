<?php namespace Books\Withdrawal\Models;

use Books\Withdrawal\Classes\Enums\EmploymentTypeEnum;
use Books\Withdrawal\Classes\Enums\WithdrawalAgreementStatusEnum;
use Books\Withdrawal\Classes\Enums\WithdrawalStatusEnum;
use Model;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * WithdrawalData Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class WithdrawalData extends Model
{
    use Validation;

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
        'employment_register_number' => 'required', //номер ИП

        'bank_beneficiary' => 'required', // Банк получатель
        'bank_inn' => 'required',
        'bank_kpp' => 'required',
        'bank_receiver' => 'required', // Получатель
        'bank_account' => 'required',
        'bank_bik' => 'required',
        'bank_corr_account' => 'required',
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
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'approved_at',
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
}
