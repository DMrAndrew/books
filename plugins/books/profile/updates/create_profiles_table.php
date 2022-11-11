<?php

namespace Books\Profile\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProfilesTable extends Migration
{
    public function up()
    {
        if(Schema::hasTable('books_profile_profiles')){
            return;
        }
        Schema::create('books_profile_profiles', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();
            $table->string('username')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_profile_profiles');
    }
}
