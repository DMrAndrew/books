<?php

namespace Books\Book\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class ChangeAdvertBookIdType extends Migration
{
    public function up()
    {
        Schema::table('books_book_adverts', function (Blueprint $table) {
            $table->unsignedBigInteger('book_id')->change();
        });

    }

    public function down()
    {
    }
}
