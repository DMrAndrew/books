<?php

namespace Books\Book\Updates;

use Books\Book\Models\Tracker;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddTrackersIp extends Migration
{
    public function up(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'ip')) {
                $table->string('ip');
            }
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn($this->table(), 'ip')) {
            Schema::dropColumns($this->table(), 'ip');
        }
    }

    protected function table(): string
    {
        return (new Tracker())->getTable();
    }
}
