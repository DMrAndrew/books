<?php

namespace Books\Book\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateBookTagTable extends Migration
{
    public function up()
    {
        Schema::create('books_book_tag', function ($table) {
            $table->integer('book_id')->unsigned()->index();
            $table->integer('tag_id')->unsigned()->index();
            $table->primary(['book_id', 'tag_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_book_tag');
    }
}
