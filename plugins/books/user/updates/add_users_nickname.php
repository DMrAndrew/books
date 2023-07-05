<?php

namespace Books\User\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateAccountSettingsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
class AddUsersNickname extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        if (!Schema::hasColumn('users', 'nickname')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nickname')->nullable();
            });
        }
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        if (Schema::hasColumn('users', 'nickname')) {
            Schema::dropColumns('users', 'nickname');
        }
    }
}
