<?php

namespace Books\Book\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddStatsFields extends Migration
{
    public function up()
    {
        Schema::table('books_book_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('collected_gain_popularity_rate')->nullable();
            $table->unsignedBigInteger('collected_hot_new_rate')->nullable();
            $table->unsignedBigInteger('collected_genre_rate')->nullable();
            $table->unsignedBigInteger('collected_popular_rate')->nullable();
            $table->unsignedBigInteger('sells_count')->nullable();
            $table->unsignedBigInteger('read_initial_count')->nullable();
            $table->unsignedBigInteger('read_final_count')->nullable();
        });

    }

    public function down()
    {
    }

}
