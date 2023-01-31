<?php namespace Books\Book\Updates;

use Schema;
use Books\Book\Classes\Enums\EditionsEnums;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateChaptersTable Migration
 */
class CreateChaptersTable extends Migration
{
    public function up()
    {
        Schema::create('books_book_chapters', function (Blueprint $table) {
            $table->id();
            $table->integer('edition_id')->unsigned()->index();
            $table->tinyInteger('type')->default(EditionsEnums::default()->value);
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->integer('length')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('sales_type')->default('free');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_book_chapters');
    }
}
