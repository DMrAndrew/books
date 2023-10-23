<?php namespace Books\Profile\Updates;

use Db;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * UpdateForeignKeys Migration
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
        /**
         * Delete rows for non-exist foreign records
         */
        $originTable = 'books_comments_comments';
        $originKey = 'user_id';

        $foreignTable = 'users';
        $foreignKey = 'id';

        $rowsDeleted = DB::table("{$originTable}")
            ->whereNotIn("{$originTable}.{$originKey}", function ($query) use ($foreignTable, $foreignKey) {
                $query->select("{$foreignKey}")
                    ->from("{$foreignTable}")
                    ->whereNotNull("{$foreignKey}");
            })
            ->delete();

        if ($rowsDeleted > 0) {
            echo "Удалено {$rowsDeleted} записей со ссылкой на несуществующие записи" . PHP_EOL;
        }

        /**
         * Add foreign key
         */
        Schema::table("{$originTable}", function (Blueprint $table) use ($foreignTable, $foreignKey, $originKey) {

            $table->unsignedInteger("{$originKey}")->change();

            $table->foreign("{$originKey}")
                ->references("{$foreignKey}")
                ->on("{$foreignTable}")
                ->onDelete('cascade');
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::table('books_comments_comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};
