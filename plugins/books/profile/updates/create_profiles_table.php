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
            $table->string('username_clipboard')->nullable();
            $table->string('status')->nullable();
            $table->text('about')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('vk')->nullable();
            $table->string('tg')->nullable();
            $table->string('ok')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_profile_profiles');
    }
}
