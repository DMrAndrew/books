<?php

namespace Books\Book\Updates;

use App;
use Books\Book\Classes\Enums\SellStatisticSellTypeEnum;
use Books\Book\Models\Book;
use Carbon\Carbon;
use RainLab\User\Models\User;
use Books\Book\Models\SellStatistics;
use October\Rain\Database\Updates\Seeder;

class seed_sell_statistics extends Seeder
{
    const SEED_RECORDS = 50;

    public function run()
    {
        /**
         * Seeding only in develop environment
         */
        if (App::environment() === 'production') {
            echo 'Skip seeding `books_sell_statistics` table in production.';

            return true;
        }

        /**
         * Get User with books
         */
        $userWithBooks = User
            ::whereHas('profiles', function ($q) {
                return $q->whereHas('books');
            })
            ->first();

        if ($userWithBooks == null) {
            echo 'Cant find author with books to seed `books_sell_statistics` table.';

            return true;
        }

        $books = [];
        $userWithBooks->profiles->map(function($profile) use (&$books){
            $books = array_merge($books, $profile->books->pluck('title', 'id')->toArray());
        });

        /**
         * Seeding sell statistics
         */
        for ($i = 0; $i <= self::SEED_RECORDS; $i++) {

            $bookId = array_keys($books)[array_rand(array_keys($books))];
            $book = Book::find($bookId);

            if ($book == null) {
                continue;
            }

            $edition = $book->editions->first();

            $sellTypes = [
                SellStatisticSellTypeEnum::SELL->value,
                SellStatisticSellTypeEnum::SUBSCRIBE->value,
            ];
            $sellType = $sellTypes[array_rand($sellTypes)];

            // цена
            $price = rand(50, 1990);

            // комиссия
            $taxRate = 20;
            $taxValue = intval($price * $taxRate / 100);

            // гонорар
            $totalReward = intval($price - $taxValue);
            $rewardRate = [20, 33, 50, 100][array_rand([20, 33, 50, 100])];
            $authorReward = intval($totalReward * $rewardRate / 100);

            $data = [
                'user_id' => $userWithBooks->id,
                'edition_id' => $bookId,
                'edition_type' => $edition->type->value,
                'sell_at' => Carbon::today()->subDays(rand(0, 365))->subHours(rand(1, 23))->subMinutes(rand(1, 55)),
                'edition_status' => $edition->status->value,
                'sell_type' => $sellType,
                'price' => $price,
                'reward_rate' => $rewardRate,
                'reward_value' => $authorReward,
                'tax_rate' => $taxRate,
                'tax_value' => $taxValue,
            ];

            SellStatistics::create($data);
        }

        echo 'Seeding completed. User with seeded sell statistics: id ' . $userWithBooks->id . PHP_EOL;

        return true;
    }
}
