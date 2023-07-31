<?php namespace Books\Withdrawal\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * UpdateBirthdayFieldType Migration
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
        Schema::table('books_withdrawal_data', function(Blueprint $table) {
            $table->date('birthday')->change();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::table('books_withdrawal_data', function(Blueprint $table) {
            $table->dateTime('birthday')->change();
        });
    }
};
