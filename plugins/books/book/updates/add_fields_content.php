<?php

namespace Books\Book\Updates;

use Books\Book\Models\Content;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

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
        });
    }

    /**
     * down reverses the migration
     */
    public function down(): void
    {
        foreach (['requested_at', 'merged_at','data'] as $column) {
            if (Schema::hasColumn($this->table(), $column)) {
                Schema::dropColumns($this->table(), $column);
            }
        }
    }

    public function table(): string
    {
        return (new Content())->getTable();
    }
};
