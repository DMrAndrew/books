<?php

namespace Books\User\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateCountriesTable Migration
 */
class CountriesAddSortOrder extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('books_user_countries', 'sort_order')) {
            Schema::table('books_user_countries', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0);
            });
        }
    }
    public function down(){}
}
