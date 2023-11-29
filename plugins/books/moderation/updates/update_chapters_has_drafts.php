<?php namespace Books\Book\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::table('books_book_chapters', function (Blueprint $table) {
            $table->drafts();
        });
    }

    public function down()
    {
        Schema::table('books_book_chapters', function (Blueprint $table) {
            $table->dropDrafts();
        });
    }
};
