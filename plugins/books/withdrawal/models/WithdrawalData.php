<?php namespace Books\Withdrawal\Models;

use Books\Book\Classes\Enums\EmploymentTypeEnum;
use Books\Book\Classes\Enums\WithdrawalAgreementStatusEnum;
use Books\Book\Classes\Enums\WithdrawalStatusEnum;
use Books\Profile\Models\Profile;
use Model;
use October\Rain\Database\Traits\Validation;

/**
 * WithdrawalData Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class WithdrawalData extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_withdrawal_data';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'profile_id' => 'required|exists:books_profile_profiles,id',
        'agreement_status' => 'required',
        'withdrawal_status' => 'required',
        'withdraw_frozen' => 'required',
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
        'approved_at' => 'required',
    ];
    public $fillable = [];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'approved_at',
        'passport_date',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'agreement_status' => WithdrawalAgreementStatusEnum::class,
        'withdrawal_status' => WithdrawalStatusEnum::class,
        'employment_type' => EmploymentTypeEnum::class,
    ];

    /**
     * @var array
     */
    public $belongsTo = [
        'profile' => [Profile::class],
    ];
}
