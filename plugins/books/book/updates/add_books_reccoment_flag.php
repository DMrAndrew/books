<?php

namespace Books\Book\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddBooksRecommendFlag extends Migration
{
    public function up()
    {
        Schema::table('books_book_books', function (Blueprint $blueprint) {
            $blueprint->boolean('recommend')->default(0);
        });
    }

    public function down()
    {
        if (Schema::hasColumn('books_book_books', 'recommend')) {
            Schema::table('books_book_books', function (Blueprint $blueprint) {
                $blueprint->dropColumn('recommend');
            });
        }
    }
}
