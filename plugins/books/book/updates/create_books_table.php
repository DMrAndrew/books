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
            $table->integer('user_id')->unsigned()->index();
            $table->integer('cycle_id')->unsigned()->nullable();
            $table->tinyInteger('age_restriction')->default(0);
            $table->integer('length')->default(0);
            $table->boolean('download_allowed')->default(true);
            $table->boolean('comment_allowed')->default(true);
            $table->string('status')->default('working');
            $table->boolean('sales_free')->default(false);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('price')->default(0);
            $table->timestamp('sales_at')->nullable();
            $table->integer('free_parts')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_book_books');
    }
}
