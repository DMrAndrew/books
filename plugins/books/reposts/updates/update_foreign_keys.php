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
         * #############################
         * Repost to user
         * #############################
         */
        echo 'Reposts table' . PHP_EOL;
        $originTable = 'books_reposts_reposts';
        $originKey = 'user_id';
        $foreignTable = 'users';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        Schema::table("{$originTable}", function (Blueprint $table) use ($foreignTable, $foreignKey, $originKey) {
            $table->unsignedInteger("{$originKey}")->change();
        });
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        $foreigns = [
            ['books_reposts_reposts', 'user_id'],
        ];
        foreach ($foreigns as $foreign) {
            [$originTable, $originKey] = $foreign;
            Schema::table($originTable, function (Blueprint $table) use ($originKey) {
                $table->dropForeign([$originKey]);
            });
        }
    }

    /**
     * Delete rows for non-exist foreign records
     *
     * @param $originTable
     * @param $originKey
     * @param $foreignTable
     * @param $foreignKey
     *
     * @return void
     */
    public function deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey): void
    {
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
    }

    /**
     * Add foreign key
     *
     * @param $originTable
     * @param $originKey
     * @param $foreignTable
     * @param $foreignKey
     *
     * @return void
     */
    public function addForeignKey($originTable, $originKey, $foreignTable, $foreignKey): void
    {
        Schema::table("{$originTable}", function (Blueprint $table) use ($foreignTable, $foreignKey, $originKey) {
            $table->foreign("{$originKey}")
                ->references("{$foreignKey}")
                ->on("{$foreignTable}")
                ->onDelete('cascade');
        });
    }
};
