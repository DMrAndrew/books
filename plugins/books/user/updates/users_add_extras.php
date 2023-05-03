<?php

namespace Books\User\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class UsersAddBirthday extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_birthday')->default(true);
            $table->boolean('see_adult')->default(false);
            $table->json('loved_genres')->nullable();
            $table->json('unloved_genres')->nullable();
            $table->boolean('required_post_register')->default(true);
            $table->boolean('asked_adult_agreement')->default(0);
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->date('birthday')->nullable();
            $table->unsignedBigInteger('state_id')->nullable()->index();
        });
    }

    public function down()
    {
        foreach ([
                     'birthday',
                     'show_birthday',
                     'see_adult', 'loved_genres',
                     'unloved_genres',
                     'required_post_register',
                     'asked_adult_agreement',
                     'country_id',
                     'state_id',
                 ] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', fn($table) => $table->dropColumn($column));
            }
        }
    }
}
