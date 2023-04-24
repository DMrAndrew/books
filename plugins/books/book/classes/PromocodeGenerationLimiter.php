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

    public Profile $profile;
    public Book $book;
    private string $reason;
    private ?Carbon $expireIn;

    public function __construct(Profile $profile, Book $book)
    {
        $this->reason = '';
        $this->expireIn = null;

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
        $userFreePromocodesPeriodEnd = $this->getUserFreePromocodesPeriodEnd();

        if (
            $now->greaterThan($userFreePromocodesPeriodEnd)
        ) {
            $this->reason = 'Период бесконечной генерации промокодов в течении первых 3 месяцев после регистрации истек';

            return false;
        }

        $this->expireIn = $userFreePromocodesPeriodEnd;

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
     * Конец периода бесконечной генерации промокодов для аккаунта
     *
     * @return Carbon
     */
    private function getUserFreePromocodesPeriodEnd(): Carbon
    {
        $profileCreatedAt = $this->profile->user->created_at;

        return $profileCreatedAt->addMonths(self::UNLIMITED_GENERATION_FREE_MONTHS);
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @return Carbon|null
     */
    public function getExpireIn(): ?Carbon
    {
        return $this->expireIn;
    }
}
