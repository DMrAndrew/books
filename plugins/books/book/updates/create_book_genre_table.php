<?php

namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateBookGenreTable extends Migration
{

    public function up()
    {
        Schema::create('books_book_genre', function ($table) {
            $table->integer('book_id')->unsigned()->index();
            $table->integer('genre_id')->unsigned()->index();
            $table->primary(['book_id', 'genre_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_book_genre');
    }
}
