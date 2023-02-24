<?php

namespace Books\Profile\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class UsersAddProfileColumn extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('users', 'current_profile_id')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('current_profile_id')->nullable();
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
