<?php namespace Books\Shop\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateOrdersTable Migration
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
        Schema::create('books_shop_orders', function(Blueprint $table) {
            $table->id();
            $table->unsignedInteger('buyer_id');
            $table->unsignedInteger('seller_id');
            $table->string('full_name');
            $table->string('phone');
            $table->unsignedBigInteger('country_id');
            $table->string('index');
            $table->text('address');
            $table->integer('amount');
            $table->timestamps();

            $table->foreign('country_id')
                ->on('books_shop_cities')
                ->references('id');
            $table->foreign('buyer_id')
                ->on('books_profile_profiles')
                ->references('id')
                ->cascadeOnDelete();
            $table->foreign('seller_id')
                ->on('books_profile_profiles')
                ->references('id')
                ->cascadeOnDelete();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_shop_orders');
    }
};
