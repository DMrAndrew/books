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
         * Authors to profile
         * #############################
         */
        echo 'Authors table' . PHP_EOL;
        $originTable = 'books_book_authors';
        $originKey = 'profile_id';
        $foreignTable = 'books_profile_profiles';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        /**
         * #############################
         * Editions to books
         * #############################
         */
        echo 'Books table' . PHP_EOL;
        $originTable = 'books_book_editions';
        $originKey = 'book_id';
        $foreignTable = 'books_book_books';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        /**
         * #############################
         * Stats to books
         * #############################
         */
        echo 'Stats table' . PHP_EOL;
        $originTable = 'books_book_stats';
        $originKey = 'book_id';
        $foreignTable = 'books_book_books';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        /**
         * #############################
         * Book awards to book, user, award
         * #############################
         */
        echo 'Book awards table' . PHP_EOL;
        $originTable = 'books_book_award_books';
        $originKey = 'book_id';
        $foreignTable = 'books_book_books';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        $originTable = 'books_book_award_books';
        $originKey = 'award_id';
        $foreignTable = 'books_book_awards';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        $originTable = 'books_book_award_books';
        $originKey = 'user_id';
        $foreignTable = 'users';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        Schema::table("{$originTable}", function (Blueprint $table) use ($foreignTable, $foreignKey, $originKey) {
            $table->unsignedInteger("{$originKey}")->change();
        });
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        /**
         * #############################
         * Advert to books
         * #############################
         */
        echo 'Adverts table' . PHP_EOL;
        $originTable = 'books_book_adverts';
        $originKey = 'book_id';
        $foreignTable = 'books_book_books';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        /**
         * #############################
         * Advert visits to user
         * #############################
         */
        echo 'Advert visit table' . PHP_EOL;
        $originTable = 'books_book_advert_visits';
        $originKey = 'user_id';
        $foreignTable = 'users';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        Schema::table("{$originTable}", function (Blueprint $table) use ($foreignTable, $foreignKey, $originKey) {
            $table->unsignedInteger("{$originKey}")->change();
        });
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        /**
         * #############################
         * Cycle to user
         * #############################
         */
        echo 'Cycle table' . PHP_EOL;
        $originTable = 'books_book_cycles';
        $originKey = 'user_id';
        $foreignTable = 'users';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        /**
         * #############################
         * Discount to edition
         * #############################
         */
        echo 'Discount table' . PHP_EOL;
        $originTable = 'books_book_discounts';
        $originKey = 'edition_id';
        $foreignTable = 'books_book_editions';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        /**
         * #############################
         * Donation to profile
         * #############################
         */
        echo 'Donations table' . PHP_EOL;
        $originTable = 'books_book_donations';
        $originKey = 'profile_id';
        $foreignTable = 'books_profile_profiles';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        /**
         * #############################
         * Trackers to user
         * #############################
         */
        echo 'Trackers table' . PHP_EOL;
        $originTable = 'books_book_trackers';
        $originKey = 'user_id';
        $foreignTable = 'users';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        Schema::table("{$originTable}", function (Blueprint $table) use ($foreignTable, $foreignKey, $originKey) {
            $table->unsignedInteger("{$originKey}")->change();
        });
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        /**
         * #############################
         * SellStatistics to user
         * #############################
         */
        echo 'SellStatistics table' . PHP_EOL;
        $originTable = 'books_sell_statistics';
        $originKey = 'edition_id';
        $foreignTable = 'books_book_editions';
        $foreignKey = 'id';

        $this->deleteNotExistedRecords($originTable, $originKey, $foreignTable, $foreignKey);
        $this->addForeignKey($originTable, $originKey, $foreignTable, $foreignKey);

        $originTable = 'books_sell_statistics';
        $originKey = 'profile_id';
        $foreignTable = 'books_profile_profiles';
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
            ['books_book_authors', 'profile_id'],
            ['books_book_editions', 'book_id'],
            ['books_book_stats', 'book_id'],

            ['books_book_award_books', 'book_id'],
            ['books_book_award_books', 'award_id'],
            ['books_book_award_books', 'user_id'],

            ['books_book_adverts', 'book_id'],
            ['books_book_advert_visits', 'user_id'],
            ['books_book_cycles', 'user_id'],
            ['books_book_discounts', 'edition_id'],
            ['books_book_donations', 'profile_id'],
            ['books_book_trackers', 'user_id'],

            ['books_sell_statistics', 'edition_id'],
            ['books_sell_statistics', 'profile_id'],
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
