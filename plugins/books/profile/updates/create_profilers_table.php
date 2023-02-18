<?php

namespace Books\Profile\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateProfilersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('books_profile_profilers')) {
            return;
        }
        Schema::create('books_profile_profilers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->morphs('master');
            $table->string('slave_type');
            $table->json('slave_ids')->default('[]');
            $table->index(['master_id', 'master_type', 'slave_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_profile_profilers');
    }
}
