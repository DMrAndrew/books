<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateDonationsTable Migration
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
        Schema::table('books_book_donations', function(Blueprint $table) {
            $table->unsignedInteger('profile_id')->nullable()->after('amount');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::table('books_book_donations', function(Blueprint $table) {
            $table->dropColumn('profile_id');
        });
    }
};
