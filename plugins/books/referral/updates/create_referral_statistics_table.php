<?php namespace Books\Referral\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateReferralStatisticsTable Migration
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
        Schema::create('books_referral_statistics', function(Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('referrer_id');
            $table->unsignedInteger('order_id');
            $table->dateTime('sell_at');
            $table->unsignedInteger('price');
            $table->unsignedInteger('reward_rate');
            $table->unsignedInteger('reward_value');
            $table->timestamps();
        });

        Schema::table('books_referral_statistics', function(Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('referrer_id')
                ->references('id')
                ->on('books_referral_referrers')
                ->onDelete('cascade');

            $table->foreign('order_id')
                ->references('id')
                ->on('books_orders_orders')
                ->onDelete('cascade');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_referral_statistics');
    }
};
