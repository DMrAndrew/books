<?php

namespace Books\Book\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateCyclesTable Migration
 */
class CreateCyclesTable extends Migration
{
    public function up()
    {
        Schema::create('books_book_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('user_id')->unsigned()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_book_cycles');
    }
}
