<?php

namespace Books\Book\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateDownloadsTable Migration
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
        Schema::create('books_book_downloads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('edition_id');
            $table->unsignedBigInteger('count');
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_downloads');
    }
};
