<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateBooksTable Migration
 */
class CreateBooksTable extends Migration
{
    public function up()
    {
        Schema::create('books_book_books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('annotation')->nullable();
            $table->integer('cycle_id')->unsigned()->nullable();
            $table->tinyInteger('age_restriction')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_book_books');
    }
}
