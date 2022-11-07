<?php namespace Books\Reviews\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateReviewsTable Migration
 */
class CreateReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('books_reviews_reviews', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('rating');
            $table->string('title');
            $table->text('body');
            $table->unsignedBigInteger('user_id');
            $table->morphs('reviewable');
            $table->timestamps();
            $table->approvals();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_reviews_reviews');
    }
}
