<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateUpdatesTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class CreateUpdatesTable extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_book_updates', function (Blueprint $table) {
            $table->id();
            $table->morphs('updatable');
            $table->json('payload');
            $table->json('meta')->nullable();
            $table->boolean('accepted')->nullable()->default(null);
            $table->uuid('batch')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_updates');
    }
}
