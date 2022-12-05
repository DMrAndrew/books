<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateCoAuthorsTable Migration
 */
class CreateCoAuthorsTable extends Migration
{
    public function up()
    {
        Schema::create('books_book_co_authors', function (Blueprint $table) {
            $table->integer('book_id')->unsigned()->index();
            $table->integer('author_id')->unsigned();
            $table->tinyInteger('percent')->default(0);
            $table->primary(['book_id','author_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_book_co_authors');
    }
}
