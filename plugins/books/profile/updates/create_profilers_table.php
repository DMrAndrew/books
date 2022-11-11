<?php

namespace Books\Profile\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProfilersTable extends Migration
{
    public function up()
    {
        if(Schema::hasTable('books_profile_profilers')){
            return;
        }
        Schema::create('books_profile_profilers', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('profile_id')->unsigned();
            $table->string('entity_type');
            $table->json('ids')->default("[]");
            $table->index(['profile_id','entity_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_profile_profilers');
    }
}
