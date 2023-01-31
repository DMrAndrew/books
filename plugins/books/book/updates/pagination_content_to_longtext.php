<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreatePaginationTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class PaginationContentToLongtext extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::table('books_book_pagination', function (Blueprint $table) {
            $table->longText('content')->change();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
    }
}
