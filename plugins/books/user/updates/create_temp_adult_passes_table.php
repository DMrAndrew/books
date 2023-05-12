<?php namespace Books\User\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateTempAdultPassesTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration {
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_user_temp_adult_passes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ipAddress('ip')->index();
            $table->boolean('is_agree')->default(false);
            $table->timestamp('expire_in');
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_user_temp_adult_passes');
    }
};
