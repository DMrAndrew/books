<?php namespace Books\Book\Updates;

use Books\Book\Models\Award;
use Books\Book\Models\Book;
use RainLab\User\Models\User;
use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateAwardBooksTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration {
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_book_award_books', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('book_id')->unsigned();
            $table->bigInteger('award_id')->unsigned();

//            $table->foreign('user_id')->references('id')->on('users');
//            $table->foreign('award_id')->references('id')->on('books_book_awards');
//            $table->foreign('book_id')->references('id')->on('books_book_books')->cascadeOnDelete();
            $table->index('book_id');
            $table->index('user_id');
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_award_books');
    }
};
