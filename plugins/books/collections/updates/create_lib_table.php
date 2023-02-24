<?php

namespace Books\Collections\Updates;

use Books\Collections\classes\CollectionEnum;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateCollectionsTable Migration
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
        Schema::create('books_collections_lib', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('book_id')->index();
            $table->boolean('loved')->default(0);
            $table->unsignedTinyInteger('type')->default(CollectionEnum::default()->value);
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_collections_lib');
    }
};
