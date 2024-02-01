<?php

namespace Books\Book\Updates;

use Books\Book\Models\Tracker;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddTrackersSimpleFree extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn($this->table(), 'parent_id')) {
            Schema::table($this->table(), function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id')->nullable()->index();
                $table->index('user_id');
                $table->index('ip');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn($this->table(), 'parent_id')) {
            Schema::dropColumns($this->table(), 'parent_id');
            Schema::table($this->table(), function (Blueprint $table) {
                $table->dropIndex(['user_id']);
                $table->dropIndex(['ip']);
                $table->dropIndex(['created_at']);
            });
        }
    }

    public function table(): string
    {
        return Tracker::make()->getTable();
    }
}
