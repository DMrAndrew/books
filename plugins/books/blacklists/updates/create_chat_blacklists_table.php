<?php namespace Books\Blacklists\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateChatBlacklistsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_blacklists_chat_blacklists', function(Blueprint $table) {
            $table->id();

            $table->unsignedInteger('owner_profile_id');
            $table->foreign('owner_profile_id')
                ->references('id')
                ->on('books_profile_profiles')
                ->cascadeOnDelete();

            $table->unsignedInteger('banned_profile_id');
            $table->foreign('banned_profile_id')
                ->references('id')
                ->on('books_profile_profiles')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_blacklists_chat_blacklists');
    }
};
