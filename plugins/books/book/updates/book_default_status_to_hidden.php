<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class BookDefaultStatusToHidden extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('books_book_books', 'status')) {
            Schema::table('books_book_books', function (Blueprint $table) {
                $table->string('status')->default('hidden')->change();
            });
        }
    }

    public function down()
    {
    }
}
