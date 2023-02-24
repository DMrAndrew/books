<?php

namespace Books\Book\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateEbookEditionsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class CreateEbookEditionsTable extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
//        Schema::create('books_book_ebook_editions', function(Blueprint $table) {
//            $table->id();
//            $table->integer('length')->default(0);
//            $table->boolean('download_allowed')->default(true);
//            $table->boolean('comment_allowed')->default(true);
//            $table->string('status')->default(BookStatus::HIDDEN->value);
//            $table->boolean('sales_free')->default(false);
//            $table->unsignedBigInteger('price')->default(0);
//            $table->timestamp('sales_at')->nullable();
//            $table->integer('free_parts')->default(0);
//            $table->timestamps();
//        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_ebook_editions');
    }
}
