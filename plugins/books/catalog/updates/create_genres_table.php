<?php namespace Books\Catalog\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateGenresTable Migration
 */
class CreateGenresTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('books_catalog_genres')) {
            return;
        }

        Schema::create('books_catalog_genres', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('desc')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('favorite')->default(false);
            $table->integer('parent_id')->nullable();
            $table->integer('nest_left')->nullable();
            $table->integer('nest_right')->nullable();
            $table->integer('nest_depth')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_catalog_genres');
    }
}
