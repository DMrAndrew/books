<?php namespace Books\Shop\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateOrderItemsTable Migration
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
        Schema::create('books_shop_order_items', function(Blueprint $table) {
            $table->id();
            $table->unsignedInteger('buyer_id');
            $table->unsignedInteger('seller_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->integer('price');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamps();

            $table->foreign('buyer_id')
                ->on('books_profile_profiles')
                ->references('id')
                ->cascadeOnDelete();
            $table->foreign('seller_id')
                ->on('books_profile_profiles')
                ->references('id')
                ->cascadeOnDelete();
            $table->foreign('product_id')
                ->on('books_shop_products')
                ->references('id')
                ->cascadeOnDelete();
            $table->foreign('order_id')
                ->on('books_shop_orders')
                ->references('id')
                ->cascadeOnDelete();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_shop_order_items');
    }
};
