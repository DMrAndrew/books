<?php

namespace Books\Book\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class ChaptersContentToLongtext extends  Migration
{
    public function up(){
        if(Schema::hasColumn('books_book_chapters','content')){
            Schema::table('books_book_chapters',function (Blueprint $table){
                $table->longText('content')->change();
            });
        }
    }
    public function down(){

    }
}
