<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * UpdateBooksAddSeoFields Migration
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
        Schema::table('books_book_books', function(Blueprint $table) {
            $table->string('h1', 255)->nullable()->after('age_restriction');
            $table->string('meta_title', 255)->nullable()->after('h1');
            $table->string('meta_desc', 255)->nullable()->after('meta_title');
            $table->text('description')->nullable()->after('meta_desc');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::table('books_book_books', function(Blueprint $table) {
            $table->dropColumn('h1');
            $table->dropColumn('meta_title');
            $table->dropColumn('meta_desc');
            $table->dropColumn('description');
        });
    }
};
