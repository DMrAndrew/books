<?php namespace Books\Book\Updates;

use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Enums\SellStatisticSellTypeEnum;
use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateAdvertVisitsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration {
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_sell_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id');
            $table->unsignedBigInteger('edition_id');
            $table->string('edition_type')->default(EditionsEnums::default()->value);
            $table->dateTime('sell_at');
            $table->string('edition_status')->default(BookStatus::WORKING->value);
            $table->string('sell_type')->default(SellStatisticSellTypeEnum::SELL->value);
            $table->unsignedInteger('price');
            $table->unsignedInteger('reward_rate');
            $table->unsignedInteger('reward_value');
            $table->unsignedInteger('tax_rate');
            $table->unsignedInteger('tax_value');
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_sell_statistics');
    }
};
