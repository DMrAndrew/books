<?php
declare(strict_types=1);

namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Books\Profile\Models\Profile;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Класс PromocodeGenerationLimiter
 *
 * Ограничивает генерацию промокодов на основе даты регистрации профиля или лимитов книги.
 */
class PromocodeGenerationLimiter
{
    public const UNLIMITED_GENERATION_FREE_MONTHS = 3;
    public const PROMOCODES_COUNT_LIMIT_FOR_BOOK_PER_MONTH = 5;
    public const UNLIMITED_PERIOD_EXPIRED_REASON = 'Период бесконечной генерации промокодов в течении первых %s месяцев после регистрации истек';
    public const BOOK_LIMIT_EXPIRED_REASON = "В этом месяце вы уже сгенерировали %s промокодов для выбранной книги";

    /**
     * PromocodeGenerationLimiter constructor.
     *
     * @param Profile $profile Профиль пользователя
     * @param Book $book Книга
     * @param CarbonInterface|null $now Текущая дата и время
     * @param string|null $reason
     * @param CarbonInterface|null $expireIn
     */

    public function __construct(
        private Profile          $profile,
        private Book             $book,
        private ?CarbonInterface  $now = null,
        private ?string          $reason = '',
        private ?CarbonInterface $expireIn = null
    )
    {
        $this->now ??= Carbon::now()->endOfDay();
    }

    /**
     * Проверяет возможность генерации промокода
     *
     * @return bool Возвращает true, если генерация промокода возможна; иначе false
     */

    public function canGenerate(): bool
    {
        return $this->allowGenerateByProfileRegistrationDate() || $this->allowGenerateByBookLimits();
    }

    /**
     * Проверяет возможность генерации промокода на основе даты регистрации профиля
     *
     * Первые 3 месяца после регистрации профиля - количество генераций промокодов неограниченно
     *
     * @return bool Возвращает true, если прошло менее 3 месяцев с даты регистрации профиля
     */

    private function allowGenerateByProfileRegistrationDate(): bool
    {
        $unlimitedExpireInDate = $this->profile->user->created_at->addMonths(self::UNLIMITED_GENERATION_FREE_MONTHS);

        if ($this->now->greaterThan($unlimitedExpireInDate)) {
            $this->reason = sprintf(self::UNLIMITED_PERIOD_EXPIRED_REASON, self::UNLIMITED_GENERATION_FREE_MONTHS);

            return false;
        }

        $this->expireIn = $unlimitedExpireInDate;

        return true;
    }

    /**
     * Проверяет возможность генерации промокода на основе лимитов книги
     *
     * Лимит - 5 промокодов/месяц на книгу
     * Не зависимо от автора (соавтора)
     * Дата обнуления количества промокодов/месяц - начало месяца
     *
     * @return bool Возвращает true, если в этом месяце было сгенерировано менее 5 промокодов для выбранной книги
     */

    private function allowGenerateByBookLimits(): bool
    {
        $exists = $this->book->ebook->promocodes()->currentMonthCreated()->count();

        if ($exists >= self::PROMOCODES_COUNT_LIMIT_FOR_BOOK_PER_MONTH) {
            $this->reason = sprintf(self::BOOK_LIMIT_EXPIRED_REASON, self::PROMOCODES_COUNT_LIMIT_FOR_BOOK_PER_MONTH);

            return false;
        }

        return true;
    }

    /**
     * Возвращает причину, по которой генерация промокода была ограничена
     *
     * @return string Причина ограничения генерации промокода
     */

    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Возвращает дату, до которой возможна безлимитная генерация промокодов
     *
     * @return CarbonInterface|null Дата окончания периода генерации промокодов
     */

    public function getExpireIn(): ?CarbonInterface
    {
        return $this->expireIn;
    }
}
