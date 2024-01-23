<?php namespace Books\Shop\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateProductsTable Migration
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
        Schema::create('books_shop_products', function(Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->integer('price');
            $table->integer('quantity');
            $table->unsignedBigInteger('category_id');
            $table->unsignedInteger('seller_id');
            $table->timestamps();

            $table->foreign('category_id')
                ->on('books_shop_categories')
                ->references('id')
                ->onDelete('cascade');

            $table->foreign('seller_id')
                ->on('books_profile_profiles')
                ->references('id')
                ->onDelete('cascade');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_shop_products');
    }
};
