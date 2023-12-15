<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateAudioReadProgressesTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_book_audio_read_progresses', function(Blueprint $table) {
            $table->id();

            $table->unsignedInteger('user_id')->index();
            $table->unsignedBigInteger('book_id')->index();
            $table->unsignedBigInteger('chapter_id')->index();
            $table->unsignedInteger('progress')->default(0);

            $table->timestamps();

            $table->foreign("user_id")
                ->references("id")
                ->on("users")
                ->onDelete('cascade');

            $table->foreign("book_id")
                ->references("id")
                ->on("books_book_books")
                ->onDelete('cascade');

            $table->foreign("chapter_id")
                ->references("id")
                ->on("books_book_chapters")
                ->onDelete('cascade');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_audio_read_progresses');
    }
};
