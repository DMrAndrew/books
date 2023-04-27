<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateAdvertsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration {
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_book_adverts', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('book_id')->index();
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('allowed_visit_count')->default(150);
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_adverts');
    }
};
