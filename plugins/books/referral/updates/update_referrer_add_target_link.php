<?php namespace Books\Referral\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * UpdateReferrerAddTargetLink Migration
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
        Schema::table('books_referral_referrers', function(Blueprint $table) {
            $table->string('target_link')->after('code');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::table('books_referral_referrers', function(Blueprint $table) {
            $table->dropColumn('target_link');
        });
    }
};
