<?php

namespace Books\Book\Updates;

use Books\Book\Models\Stats;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddStatsFields extends Migration
{
    protected array $columns = [
        'collected_gain_popularity_rate',
        'collected_hot_new_rate',
        'collected_genre_rate',
        'collected_popular_rate',
        'sells_count',
        'read_initial_count',
        'read_final_count',
    ];

    protected function table(): string
    {
        return (new Stats())->getTable();
    }

    public function up(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            foreach ($this->columns as $column) {
                Schema::hasColumn($this->table(), $column) ?: $table->unsignedBigInteger($column)->nullable();
            }
        });

    }

    public function down(): void
    {
        foreach ($this->columns as $column) {
            !Schema::hasColumn($this->table(), $column) ?: Schema::dropColumns($this->table(), $column);
        }
    }

}
