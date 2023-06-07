<?php namespace Books\Payment\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreatePaymentTable Migration
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
        Schema::table('books_payment_payments', function(Blueprint $table) {
            $table->string('transaction_id')->after('payment_id')->nullable();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::table('books_payment_payments', function(Blueprint $table) {
            $table->dropColumn('transaction_id');
        });
    }
};
