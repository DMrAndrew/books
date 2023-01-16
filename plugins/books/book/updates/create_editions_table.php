<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

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
            $table->morphs('editionable');
            $table->index(['editionable_id']);
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
