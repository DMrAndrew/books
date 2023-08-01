<?php

namespace Books\Book\Updates;

use Schema;
use Books\Book\Models\Content;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration {
    /**
     * up builds the migration
     */
    public function up(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('merged_at')->nullable();
            $table->json('data')->nullable();
            $table->unsignedTinyInteger('status')->nullable();
            $table->boolean('saved_from_editor')->default(false);

        });
        if (Schema::hasColumn($this->table(), 'fillable_type')) {
            Schema::table($this->table(), function (Blueprint $table) {
                $table->renameColumn('fillable_type', 'contentable_type');
                $table->renameColumn('fillable_id', 'contentable_id');
            });
        }
        Schema::table($this->table(), function (Blueprint $table) {
            $table->index('type');
        });
    }

    /**
     * down reverses the migration
     */
    public function down(): void
    {
        foreach (['requested_at', 'merged_at', 'data', 'status','saved_from_editor'] as $column) {
            if (Schema::hasColumn($this->table(), $column)) {
                Schema::dropColumns($this->table(), $column);
            }
        }
        if (Schema::hasColumn($this->table(), 'contentable_type')) {
            Schema::table($this->table(), function (Blueprint $table) {
                $table->renameColumn('contentable_type', 'fillable_type');
                $table->renameColumn('contentable_id', 'fillable_id');
                $table->dropIndex(['type']);
            });
        }
    }

    public function table(): string
    {
        return (new Content())->getTable();
    }
};
