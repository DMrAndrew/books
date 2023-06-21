<?php

namespace Books\Book\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddTrackersIp extends Migration
{
    public function up(): void
    {
        Schema::table('books_book_trackers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('ip');
        });
    }

    public function down()
    {
    }
}
