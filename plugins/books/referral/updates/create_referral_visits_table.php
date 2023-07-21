<?php namespace Books\Referral\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateReferralVisitsTable Migration
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
        Schema::create('books_referral_referral_visits', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id');
            $table->timestamps();
        });

        Schema::table('books_referral_referral_visits', function(Blueprint $table) {
            $table->foreign('referrer_id')
                ->references('id')
                ->on('books_referral_referrers')
                ->onDelete('cascade');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_referral_referral_visits');
    }
};
