<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateChaptersTable Migration
 */
class CreateChaptersTable extends Migration
{
    public function up()
    {
        Schema::create('books_book_chapters', function (Blueprint $table) {
            $table->id();
            $table->integer('book_id')->unsigned()->index();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->integer('length')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('edition')->default('free');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_book_chapters');
    }
}
