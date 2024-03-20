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
        Schema::table('books_shop_orders', function(Blueprint $table) {
            $table->integer('status')->default(1)->after('amount');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropColumns('books_shop_orders', 'status');
    }
};
