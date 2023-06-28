<?php

namespace Books\Book\Updates;

use Books\Book\Models\Promocode;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateUpdatesTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class update_promocodes_table_add_used_by_profile_id extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::table('books_book_promocodes', function (Blueprint $table) {
            $table->unsignedInteger('used_by_profile_id')->after('user_id')->nullable();
            $table->foreign('used_by_profile_id')->references('id')->on('books_profile_profiles')->cascadeOnDelete();
        });

        $promocodesToUpdate = Promocode::with('user')->whereNotNull('user_id');
        $promocodesToUpdate->each(function($promocode) {
            $promocode->used_by_profile_id = $promocode->user->profile->id;
            $promocode->save();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::table('books_book_promocodes', function (Blueprint $table) {
            $table->dropForeign(['used_by_profile_id']);
            $table->dropColumn('used_by_profile_id');
        });
    }
}
