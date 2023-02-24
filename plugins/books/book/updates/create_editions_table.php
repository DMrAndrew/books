<?php

namespace Books\Book\Updates;

use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\EditionsEnums;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateEditionsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class CreateEditionsTable extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_book_editions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('book_id')->index();
            $table->integer('length')->default(0);
            $table->tinyInteger('type')->default(EditionsEnums::default()->value);
            $table->boolean('download_allowed')->default(true);
            $table->boolean('comment_allowed')->default(true);
            $table->string('status')->default(BookStatus::HIDDEN->value);
            $table->boolean('sales_free')->default(false);
            $table->unsignedBigInteger('price')->default(0);
            $table->timestamp('sales_at')->nullable();
            $table->integer('free_parts')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_editions');
    }
}
