<?php namespace Books\Catalog\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * UpdateListingAddSeoFields Migration
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
        Schema::table('books_catalog_types', function(Blueprint $table) {
            $table->string('slug', 255)->nullable()->after('sort_order');
            $table->text('desc')->nullable()->after('slug');
            $table->string('h1', 255)->nullable()->after('desc');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::table('books_catalog_types', function(Blueprint $table) {
            $table->dropColumn('slug');
            $table->dropColumn('desc');
            $table->dropColumn('h1');
        });
    }
};
