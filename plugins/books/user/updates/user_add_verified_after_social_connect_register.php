<?php

namespace Books\User\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UserAddVerifiedAfterSocialConnectRegister extends Migration
{
    public function up()
    {
        Schema::table('users', function ($table) {
            $table->boolean('required_post_register')->default(true);
        });
    }

    public function down()
    {
        Schema::table('users', function ($table) {
            $table->dropColumn('required_post_register');
        });
    }
}
