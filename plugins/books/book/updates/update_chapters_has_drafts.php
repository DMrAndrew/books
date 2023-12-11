<?php namespace Books\Book\Updates;

use Books\Book\Models\Chapter;
use October\Rain\Database\Collection;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::table('books_book_chapters', function (Blueprint $table) {
            $table->drafts();
        });

        /**
         * Fill moderation data for existing records
         */
        Chapter::query()
            ->withTrashed()
            ->whereNull('moderation_uuid')
            ->chunkById(50, function (Collection $models) {
                $models->each(function ($model) {
                    $model->generateUuid();
                    $model->setLive();
                    $model->saveQuietly();

                    return true;
                });

            return true;
        });
    }

    public function down()
    {
        Schema::table('books_book_chapters', function (Blueprint $table) {
            $table->dropDrafts();
        });
    }
};
