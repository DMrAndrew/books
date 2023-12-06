<?php namespace Books\AuthorPrograms\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateAuthorsProgramsTable Migration
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
        Schema::create('books_authors_programs', function(Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('program', 100);
            $table->jsonb('condition');
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_authorprograms_authors_programs');
    }
};
