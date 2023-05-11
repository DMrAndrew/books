<?php namespace Books\Payment\Updates;

use Books\Payment\Classes\Enums\PaymentStatusEnum;
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
            $table->unsignedInteger('order_id')->nullable();
            $table->string('payment_id');
            $table->unsignedInteger('payer_id');
            $table->string('payer_email');
            $table->float('amount', 10, 2);
            $table->string('currency');
            $table->tinyInteger('payment_status')->default(PaymentStatusEnum::CREATED->value);
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('books_orders_orders')
                ->onDelete('cascade');

            $table->foreign('payer_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
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
