<?php namespace Books\Referral\Models;

use Carbon\Carbon;
use Exception;
use Model;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;
use Str;

/**
 * Referrer Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Referrer extends Model
{
    use Validation;

    const REFERRAL_PARTNER_CODE_LENGTH = 6;
    const MAX_ATTEMPTS = 10;

    /**
     * @var string table name
     */
    public $table = 'books_referral_referrers';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'code' => 'required|unique:code',
        'user_id' => 'required|integer',
    ];

    public $belongsTo = [
        'user' => User::class,
    ];

    public $hasMany = [
        'referrals' => User::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($referrer) {
            $referrer->checkUserAge();
            $referrer->uniqueCodeGenerator();
        });
    }

    /**
     * Генерирует уникальный код партнера
     *
     * @return void
     * @throws Exception Если уникальный код не может быть сгенерирован после максимального количества попыток.
     */
    public function uniqueCodeGenerator(): void
    {
        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $code = static::gen();
            if (!static::query()->code($code)->exists()) {
                $this->attributes['code'] = $code;
                return;
            }
        }

        throw new Exception('Не удалось сгенерировать код партнера после ' . self::MAX_ATTEMPTS . ' попыток');
    }

    /**
     * Генерирует уникальную строку с использованием текущего времени и случайной строки.
     * Полученная строка преобразуется в верхний регистр и хэшируется с использованием алгоритма xxh32.
     *
     * @return string Сгенерированная уникальная строка.
     */
    public static function gen(): string
    {
        // Получение текущего времени в формате ISO 8601
        $timestamp = Carbon::now()->toISOString();

        // Генерация случайной строки
        $randomString = Str::random(self::REFERRAL_PARTNER_CODE_LENGTH);

        return strtoupper(hash('xxh32', $timestamp . $randomString));
    }

    public function checkUserAge(): void
    {
        dd($this->user);

        return;

        //throw new Exception('Минимальный возраст для участия в реферальной программе составляет - 18 лет');
    }
}
