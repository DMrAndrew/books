<?php namespace Books\Referral\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateReferrersTable Migration
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
        Schema::create('books_referral_referrers', function(Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::table('books_referral_referrers', function(Blueprint $table) {
            $table->foreign('user_id')
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
        Schema::dropIfExists('books_referral_referrers');
    }
};
