<?php
declare(strict_types=1);

namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Books\Profile\Models\Profile;
use Carbon\Carbon;

class PromocodeGenerationLimiter
{
    const UNLIMITED_GENERATION_FREE_MONTHS = 3;
    const PROMOCODES_COUNT_LIMIT_FOR_BOOK_PER_MONTH = 5;

    public string $reason;
    public Profile $profile;
    public Book $book;

    public function __construct(Profile $profile, Book $book)
    {
        $this->reason = '';

        $this->profile = $profile;
        $this->book = $book;
    }

    public function checkCanGenerate(): bool
    {
        if ($this->allowGenerateByProfileRegistrationDate()) {
            return true;
        }

        if ( $this->allowGenerateByBookLimits()) {
            return true;
        }

        return false;
    }

    /**
     * Первые 3 месяца после регистрации профиля - количество генераций промокодов неограничено
     */
    private function allowGenerateByProfileRegistrationDate(): bool
    {
        $now = Carbon::now()->endOfDay();
        $profileCreatedAt = $this->profile->created_at;

        if (
            $now->greaterThan($profileCreatedAt->addMonths(self::UNLIMITED_GENERATION_FREE_MONTHS))
        ) {
            $this->reason = 'Период бесконечной генерации промокодов в течении первых 3 месяцев после регистрации истек';

            return false;
        }

        return true;
    }

    /**
     * Лимит - 5 промокодов/месяц на книгу
     * Не зависимо от автора (соавтора)
     * Дата обнуления количества промокодов/месяц - начало месяца
     */
    private function allowGenerateByBookLimits(): bool
    {
        $currentMonth = Carbon::now()->startOfMonth();

        $currentMonthAlreadyGeneratedBookPromocodesCount = $this->book->promocodes
            ->where('created_at', '>', $currentMonth)
            ->count();

        if ($currentMonthAlreadyGeneratedBookPromocodesCount >= self::PROMOCODES_COUNT_LIMIT_FOR_BOOK_PER_MONTH) {
            $this->reason = "В этом месяце вы уже сгенерировали " . self::PROMOCODES_COUNT_LIMIT_FOR_BOOK_PER_MONTH . " промокодов для выбранной книги";

            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
