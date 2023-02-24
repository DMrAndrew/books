<?php

namespace Books\Profile\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateProfilesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('books_profile_profiles')) {
            return;
        }
        Schema::create('books_profile_profiles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('username');
            $table->string('username_clipboard')->nullable();
            $table->string('username_clipboard_comment')->nullable();
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
