<?php

namespace Books\User\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class UsersBirthdayToDate extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('birthday')->change();
        });
    }

    public function down()
    {
    }
}
