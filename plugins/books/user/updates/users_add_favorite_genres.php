<?php

namespace Books\User\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UsersAddFavoriteGenres extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'favorite_genres')) {
            Schema::table('users', function ($table) {
                $table->json('favorite_genres')->default('[]');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'favorite_genres')) {
            Schema::table('users', function ($table) {
                $table->dropColumn('favorite_genres');
            });
        }
    }
}
