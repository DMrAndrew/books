<?php

namespace Books\Book\Updates;

use Books\Book\Classes\Enums\EditionsEnums;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreatePaginationTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class CreatePaginationTable extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_book_pagination', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chapter_id');
            $table->integer('page');
//            $table->longText('content')->nullable();
            $table->integer('length')->nullable();
            $table->tinyInteger('type')->default(EditionsEnums::default()->value);
            $table->timestamps();

            $table->unsignedBigInteger('next_id')->nullable();
            $table->unsignedBigInteger('prev_id')->nullable();

            $table->foreign('chapter_id')->references('id')->on('books_book_chapters')->cascadeOnDelete();
            $table->index('chapter_id');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_pagination');
    }
}
