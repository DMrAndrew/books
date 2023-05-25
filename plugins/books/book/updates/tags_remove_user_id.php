<?php

namespace Books\Book\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class TagsRemoveUserId extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('books_book_tags', 'user_id')) {
            Schema::table('books_book_tags', function (Blueprint $blueprint) {
                $blueprint->dropColumn('user_id');
            });
        }
    }

    public function down()
    {
    }
}
