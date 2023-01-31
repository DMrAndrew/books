<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

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
            $table->unsignedBigInteger('paginator_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('sec')->default(0);
            $table->integer('length')->default(0);
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
