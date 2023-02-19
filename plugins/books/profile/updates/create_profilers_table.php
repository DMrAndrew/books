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
            $table->morphs('master', 'master');
            $table->morphs('slave', 'slave');
            $table->primary(['master_type', 'slave_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_profile_profilers');
    }
}
