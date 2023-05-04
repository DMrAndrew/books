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
        Schema::create('books_payment_payments', function(Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('payment_id');
            $table->string('payer_id');
            $table->string('payer_email');
            $table->float('amount', 10, 2);
            $table->string('currency');
            $table->string('payment_status');
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_payment_payments');
    }
};
