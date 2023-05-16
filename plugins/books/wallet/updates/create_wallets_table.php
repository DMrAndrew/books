<?php

declare(strict_types=1);

namespace Books\Wallet\Updates;

use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use October\Rain\Support\Facades\Schema;

class CreateWalletsTable extends Migration
{
    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('holder');
            $table->string('name');
            $table->string('slug')
                ->index()
            ;
            $table->uuid('uuid')
                ->unique()
            ;
            $table->string('description')
                ->nullable()
            ;
            $table->json('meta')
                ->nullable()
            ;
            $table->decimal('balance', 64, 0)
                ->default(0)
            ;
            $table->unsignedSmallInteger('decimal_places')
                ->default(2)
            ;
            $table->timestamps();

            $table->unique(['holder_type', 'holder_id', 'slug']);
        });

        Schema::table($this->transactionTable(), function (Blueprint $table) {
            $table->foreign('wallet_id')
                ->references('id')
                ->on($this->table())
                ->onDelete('cascade')
            ;
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::drop($this->table());
    }

    protected function table(): string
    {
        return (new Wallet())->getTable();
    }

    private function transactionTable(): string
    {
        return (new Transaction())->getTable();
    }
}
