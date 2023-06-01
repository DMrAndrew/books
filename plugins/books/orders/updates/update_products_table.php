<?php namespace Books\Orders\Updates;

use Books\Orders\Classes\Enums\OrderStatusEnum;
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
    public function up()
    {
        Schema::table('books_orders_products', function(Blueprint $table) {
            $table->unsignedInteger('book_id')->nullable()->after('orderable_id');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::table('books_orders_products', function(Blueprint $table) {
            $table->dropColumn('book_id');
        });
    }
};
