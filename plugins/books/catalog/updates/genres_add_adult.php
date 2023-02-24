<?php

namespace Books\Catalog\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class GenresAddAdult extends Migration
{
    public function up()
    {
        Schema::table('books_catalog_genres', function (Blueprint $blueprint) {
            $blueprint->boolean('adult')->default(false);
        });
    }
    public function down(){}
}

