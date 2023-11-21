<?php

namespace Books\Comments\Updates;

use Books\Comments\Models\Comment;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * CreateCommentsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration
{
    protected string $column = 'deleted_by_id';

    /**
     * up builds the migration
     */
    public function up(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            $table->unsignedBigInteger($this->column)->nullable();
        });
    }

    /**
     * down reverses the migration
     */
    public function down(): void
    {
        if (Schema::hasColumn($this->table(), $this->column)) {
            Schema::dropColumns($this->table(), $this->column);
        }
    }

    protected function table(): string
    {
        return Comment::make()->getTable();
    }
};
