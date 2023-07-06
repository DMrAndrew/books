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
        Schema::table('books_withdrawal_data', function(Blueprint $table) {
            $table->string('approve_code', 256)->nullable()->after('bank_corr_account');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::table('books_withdrawal_data', function(Blueprint $table) {
            $table->dropColumn('approve_code');
        });
    }
};
