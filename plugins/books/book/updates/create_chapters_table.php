<?php

namespace Books\Book\Updates;

use Books\Book\Classes\Enums\EditionsEnums;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateChaptersTable Migration
 */
class CreateChaptersTable extends Migration
{
    public function up()
    {
        Schema::create('books_book_chapters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edition_id');
            $table->tinyInteger('type')->default(EditionsEnums::default()->value);
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->integer('length')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('sales_type')->default('free');
            $table->softDeletes();
            $table->timestamps();

            $table->unsignedBigInteger('next_id')->nullable();
            $table->unsignedBigInteger('prev_id')->nullable();

            $table->foreign('edition_id')->references('id')->on('books_book_editions')->cascadeOnDelete();
            $table->index('edition_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_book_chapters');
    }
}
