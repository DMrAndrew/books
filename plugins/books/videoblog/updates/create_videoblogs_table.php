<?php

declare(strict_types=1);

namespace Books\Videoblog\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateVideoblogsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class CreateVideoblogsTable extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_videoblog_videoblogs', function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('profile_id');

            $table->foreign('profile_id')
                ->references('id')
                ->on('books_profile_profiles')
                ->cascadeOnDelete();

            $table->string('status')->default('draft');
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->string('link', 255);
            $table->string('embed', 255);
            $table->text('content');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_videoblog_videoblogs');
    }
};
