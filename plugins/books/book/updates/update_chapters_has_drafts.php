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
        /**
         * For multiple retries on deploy
         */
        if (! Schema::hasColumn(
            'books_book_chapters',
            config('books.moderation::column_names.is_published', 'is_published'))
        ){
            Schema::table('books_book_chapters', function (Blueprint $table) {
                $table->drafts();
            });
        }

        /**
         * Fill moderation data for existing records
         */
        Chapter::query()
            ->withTrashed()
            ->whereNull('moderation_uuid')
            ->chunkById(100, function (Collection $models) {
                foreach ($models as $model) {
                    $model->generateUuid();
                    $model->setLive();
                    $model->saveQuietly();
                }

                unset($models);

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
