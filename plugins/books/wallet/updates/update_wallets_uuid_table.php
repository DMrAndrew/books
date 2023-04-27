<?php

declare(strict_types=1);

namespace Books\Wallet\Updates;

use Bavix\Wallet\Internal\Service\UuidFactoryServiceInterface;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Support\Collection;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use October\Rain\Support\Facades\Schema;

class UpdateWalletsUuidTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn($this->table(), 'uuid')) {
            return;
        }

        // upgrade from 6.x
        Schema::table($this->table(), function (Blueprint $table) {
            $table->uuid('uuid')
                ->after('slug')
                ->nullable()
                ->unique()
            ;
        });

        Wallet::query()->chunk(10000, static function (Collection $wallets) {
            $wallets->each(function (Wallet $wallet) {
                $wallet->uuid = app(UuidFactoryServiceInterface::class)->uuid4();
                $wallet->save();
            });
        });

        Schema::table($this->table(), static function (Blueprint $table) {
            $table->uuid('uuid')
                ->change()
            ;
        });
    }

    public function down(): void
    {
        Schema::dropColumns($this->table(), ['uuid']);
    }

    protected function table(): string
    {
        return (new Wallet())->getTable();
    }
}
