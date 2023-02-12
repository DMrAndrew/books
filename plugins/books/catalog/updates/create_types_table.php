<?php

namespace Books\Catalog\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateTypesTable Migration
 */
class CreateTypesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('books_catalog_types')) {
            return;
        }
        Schema::create('books_catalog_types', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('desc')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_catalog_types');
    }
}
