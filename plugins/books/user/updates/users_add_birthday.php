<?php

namespace Books\User\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UsersAddBirthday extends Migration
{
    public function up()
    {
        Schema::table('users', function ($table) {
            $table->date('birthday')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function ($table) {
            $table->dropColumn('birthday');
        });
    }
}
