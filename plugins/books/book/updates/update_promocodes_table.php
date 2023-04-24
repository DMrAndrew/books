<?php

namespace Books\Book\Updates;

<<<<<<< HEAD
=======
use Books\Book\Models\Promocode;
>>>>>>> a0f2ac5e7aeff07467245e2946d9bacfb1cfff96
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateUpdatesTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class update_promocodes_table extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
<<<<<<< HEAD
=======
        Promocode::all()->each(function($promocode) {
            $promocode->delete();
        });

>>>>>>> a0f2ac5e7aeff07467245e2946d9bacfb1cfff96
        Schema::table('books_book_promocodes', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropColumn('book_id');
            $table->dropColumn('expire_in');
        });

        Schema::table('books_book_promocodes', function (Blueprint $table) {
            $table->morphs('promoable');
            $table->timestamp('expire_in')->nullable()->after('updated_at');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
<<<<<<< HEAD
=======
        Promocode::all()->each(function($promocode) {
            $promocode->delete();
        });

>>>>>>> a0f2ac5e7aeff07467245e2946d9bacfb1cfff96
        Schema::table('books_book_promocodes', function (Blueprint $table) {
            $table->dropColumn('expire_in');
        });

        Schema::table('books_book_promocodes', function (Blueprint $table) {
            $table->dropColumn('promoable_type');
            $table->dropColumn('promoable_id');

            // book
            $table->unsignedBigInteger('book_id')->after('code');
            $table->foreign('book_id')->references('id')->on('books_book_books')->cascadeOnDelete();

            $table->timestamp('expire_in')->useCurrent();
        });
    }
}
