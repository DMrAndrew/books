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
    private string $reason = '';
    const unlimited_period_expired_reason = 'Период бесконечной генерации промокодов в течении первых ' . self::UNLIMITED_GENERATION_FREE_MONTHS . ' месяцев после регистрации истек';
    const book_limit_expired_reason = "В этом месяце вы уже сгенерировали " . self::PROMOCODES_COUNT_LIMIT_FOR_BOOK_PER_MONTH . " промокодов для выбранной книги";
    private ?Carbon $expireIn = null;

    public function __construct(public Profile $profile, public Book $book)
    {
    }

    public function checkCanGenerate(): bool
    {
        return $this->allowGenerateByProfileRegistrationDate() || $this->allowGenerateByBookLimits();
    }

    /**
     * Первые 3 месяца после регистрации профиля - количество генераций промокодов неограничено
     */
    private function allowGenerateByProfileRegistrationDate(): bool
    {
        $unlimited_expire_in_date = $this->profile
            ->user
            ->created_at
            ->copy()
            ->addMonths(self::UNLIMITED_GENERATION_FREE_MONTHS); //Конец периода бесконечной генерации промокодов для аккаунта

        if (Carbon::now()->endOfDay()->greaterThan($unlimited_expire_in_date)) {
            $this->reason = self::unlimited_period_expired_reason;

            return false;
        }

        $this->expireIn = $unlimited_expire_in_date;

        return true;
    }

    /**
     * Лимит - 5 промокодов/месяц на книгу
     * Не зависимо от автора (соавтора)
     * Дата обнуления количества промокодов/месяц - начало месяца
     */
    private function allowGenerateByBookLimits(): bool
    {
        $exists = $this->book->ebook
            ->promocodes()
            ->currentMonthCreated()
            ->count();

        if ($exists >= self::PROMOCODES_COUNT_LIMIT_FOR_BOOK_PER_MONTH) {
            $this->reason = self::book_limit_expired_reason;

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

    /**
     * @return Carbon|null
     */
    public function getExpireIn(): ?Carbon
    {
        return $this->expireIn;
    }
}
