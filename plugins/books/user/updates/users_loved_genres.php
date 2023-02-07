<?php

namespace Books\User\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class UsersLovedGenres extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->json('loved_genres')->nullable();
            $blueprint->json('unloved_genres')->nullable();
        });
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'loved_genres')) {
            Schema::table('users', function (Blueprint $blueprint) {
                $blueprint->dropColumn('loved_genres');
                $blueprint->dropColumn('unloved_genres');
            });
        };
    }
}
