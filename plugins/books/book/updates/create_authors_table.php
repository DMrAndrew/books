<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateAuthorsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class CreateAuthorsTable extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_book_authors', function(Blueprint $table) {
            $table->id();
            $table->integer('profile_id')->unsigned()->index();
            $table->integer('book_id')->unsigned()->index();
            $table->boolean('is_owner')->default(0);
            $table->tinyInteger('percent')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_authors');
    }
}
