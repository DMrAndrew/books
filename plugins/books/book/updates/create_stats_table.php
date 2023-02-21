<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateStatsTable Migration
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
        Schema::create('books_book_stats', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id')->index();
            $table->unsignedBigInteger('likes_count')->nullable();
            $table->unsignedBigInteger('in_lib_count')->nullable();
            $table->unsignedBigInteger('read_count')->nullable();
            $table->unsignedBigInteger('comments_count')->nullable();
            $table->unsignedBigInteger('rate')->nullable();
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_stats');
    }
};
