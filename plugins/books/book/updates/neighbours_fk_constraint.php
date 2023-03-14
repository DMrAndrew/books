<?php

namespace Books\Book\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class NeighboursFkConstraint extends Migration
{
    public function up()
    {
        foreach (['books_book_chapters', 'books_book_pagination'] as $table) {
            collect(['next_id', 'prev_id'])->each(fn ($сolumn) => Schema::hasColumn($table, $сolumn) && Schema::dropColumns($table, $сolumn));

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unsignedBigInteger('next_id')->nullable();
                $blueprint->unsignedBigInteger('prev_id')->nullable();
                $blueprint->foreign('next_id')->references('id')->on($table)->nullOnDelete();
                $blueprint->foreign('prev_id')->references('id')->on($table)->nullOnDelete();
            });
        }
    }

    public function down()
    {
    }
}
