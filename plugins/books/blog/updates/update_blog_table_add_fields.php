<?php namespace Books\Blog\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * UpdateBlogTableAddFields Migration
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
        Schema::table('books_blog_posts', function(Blueprint $table) {
            $table->string('status')->default('draft')->after('profile_id');
            $table->timestamp('published_at')->nullable()->after('content');
            $table->softDeletes();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::table('books_blog_posts', function(Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('published_at');
            $table->dropColumn('deleted_at');
        });
    }
};
