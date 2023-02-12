<?php

namespace Books\User\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateCountriesTable Migration
 */
class CreateCountriesTable extends Migration
{
    public function up()
    {
        Schema::create('books_user_countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->char('code', 3)->unique()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_user_countries');
    }
}
