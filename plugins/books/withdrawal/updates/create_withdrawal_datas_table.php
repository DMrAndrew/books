<?php namespace Books\Withdrawal\Updates;

use Books\Withdrawal\Classes\Enums\EmploymentTypeEnum;
use Books\Withdrawal\Classes\Enums\WithdrawalAgreementStatusEnum;
use Books\Withdrawal\Classes\Enums\WithdrawalStatusEnum;
use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateWithdrawalDatasTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_withdrawal_data', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            $table->string('agreement_status')->default(WithdrawalAgreementStatusEnum::SIGNING->value);
            $table->string('withdrawal_status')->default(WithdrawalStatusEnum::ALLOWED->value);
            $table->boolean('withdraw_frozen')->default(false);

            $table->string('fio')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('birthday')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('passport_date')->nullable();
            $table->string('passport_issued_by')->nullable();
            $table->string('address')->nullable();
            $table->string('inn')->nullable();

            $table->string('employment_type')->default(EmploymentTypeEnum::INDIVIDUAL->value);
            $table->string('employment_register_number')->nullable(); //номер ИП

            $table->string('bank_beneficiary'); // Банк получатель
            $table->string('bank_inn');
            $table->string('bank_kpp');
            $table->string('bank_receiver'); // Получатель
            $table->string('bank_account');
            $table->string('bank_bik');
            $table->string('bank_corr_account');

            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_withdrawal_data');
    }
};
