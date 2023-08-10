<?php namespace Books\Blog\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateBlogsTable Migration
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
        Schema::create('books_blog_posts', function(Blueprint $table) {
            $table->id();

            $table->unsignedInteger('profile_id');
            $table->foreign('profile_id')
                ->references('id')
                ->on('books_profile_profiles')
                ->cascadeOnDelete();

            $table->string('title', 255);
            $table->string('slug', 255);
            $table->text('preview');
            $table->text('content');

            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_blog_posts');
    }
};
