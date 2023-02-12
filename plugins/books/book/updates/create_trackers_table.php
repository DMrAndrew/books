<?php

namespace Books\Book\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateTrackersTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class CreateTrackersTable extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_book_trackers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->morphs('trackable');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedBigInteger('time')->default(0);
            $table->unsignedBigInteger('length')->default(0);
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_trackers');
    }
}
