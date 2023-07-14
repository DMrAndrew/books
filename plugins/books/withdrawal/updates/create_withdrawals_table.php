<?php namespace Books\Withdrawal\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateWithdrawalsTable Migration
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
        Schema::create('books_withdrawal_withdrawals', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('amount');
            $table->dateTime('date');
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_withdrawal_withdrawals');
    }
};
