<?php

namespace Books\User\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class UsersAddBirthday extends Migration
{
    public function up()
    {
        Schema::table('users', function ($table) {
            $table->timestamp('birthday')->nullable();
            $table->boolean('show_birthday')->default(true);
            $table->unsignedBigInteger('country_id')->nullable();
            $table->boolean('see_adult')->default(false);
            $table->json('favorite_genres')->default('[]');
            $table->json('exclude_genres')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function ($table) {
            $table->dropColumn('birthday');
            $table->dropColumn('show_birthday');
            $table->dropColumn('country_id');
            $table->dropColumn('see_adult');
            $table->dropColumn('favorite_genres');
            $table->dropColumn('exclude_genres');
        });
    }
}
