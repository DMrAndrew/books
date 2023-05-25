<?php

namespace Books\Book\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class StatsTableAddFields extends Migration
{
    public function up()
    {
        Schema::table('books_book_stats', function (Blueprint $blueprint) {
            $blueprint->unsignedInteger('read_time')->default(0);
            $blueprint->unsignedInteger('freq')->default(0);
            $blueprint->json('history')->default('[]');
        });
    }

    public function down()
    {
        Schema::table('books_book_stats', function (Blueprint $blueprint) {
            $blueprint->dropColumn('read_time');
            $blueprint->dropColumn('freq');
            $blueprint->dropColumn('history');
        });
    }
}
