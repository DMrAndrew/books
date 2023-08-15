<?php namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * UpdatePromocodesIdFieldIntSize Migration
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
        Schema::table('books_orders_promocodes', function (Blueprint $table) {
            $table->dropForeign(['promocode_id']);
        });

        Schema::table('books_book_promocodes', function(Blueprint $table) {
            $table->unsignedBigInteger('id')->change();
        });

        Schema::table('books_orders_promocodes', function (Blueprint $table) {
            $table->unsignedBigInteger('promocode_id')->change();
        });

        Schema::table('books_orders_promocodes', function (Blueprint $table) {
            $table->foreign('promocode_id')
                ->references('id')
                ->on('books_book_promocodes')
                ->onDelete('cascade');
        });
    }

    /**
     * up builds the migration
     */
    public function down()
    {
    }
};
