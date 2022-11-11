<?php

namespace Books\Profile\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UsersAddProfileColumn extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('users', 'current_profile_id')) {
            return;
        }
        Schema::table('users', function ($table) {
            $table->integer('current_profile_id')->unsigned()->nullable();
        });
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'current_profile_id')) {
            Schema::table('users', function ($table) {
                $table->dropColumn('current_profile_id');
            });
        }

    }
}
