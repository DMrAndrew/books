<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateDiscountsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration {
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_book_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edition_id')->nullable();
            $table->unsignedTinyInteger('amount');
            $table->timestamp('active_at');
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_discounts');
    }
};
