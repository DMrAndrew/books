<?php declare(strict_types=1);

namespace Books\Book\Models;

use App\traits\HasProfileScope;
use Books\Book\Classes\CodeGenerator;
use Books\Profile\Models\Profile;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Prunable;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;
use Str;

/**
 * Promocode Model
 *
 * - Промокод привязан к книге и одновременно к профилю
 * - Каждый соавтор может генерировать промокоды к одной и той же книге
 * - Лимиты на генерацию промокодов для одной книги:
 *      - первые 3 месяца количество промокодов неограничено
 *      - после 3х месяцев - 5 промокодов/месяц на книгу
 *      - дата обнуления количества промокодов/месяц - начало месяца
 *      - промокоды могут накапливаться (переходить из месяца в месяц)
 * - На 4й месяц, промокоды, которые были сгенерированы в безлимитный период сгорают
 *
 * Лимиты на генерацию в классе @link PromocodeGenerationLimiter
 */
class Promocode extends Model
{
    use Validation;
    use Prunable;
    use HasProfileScope;

    /**
     * Максимальное количество попыток генерации уникального кода
     */
    const MAX_ATTEMPTS = 10;
    const CODE_LENGTH = 8;

    /**
     * @var string table name
     */
    public $table = 'books_book_promocodes';

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'code',
        'profile_id',
        'is_activated',
        'user_id',
        'used_by_profile_id',
        'activated_at',
        'expire_in',
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'code' => 'string|unique:books_book_promocodes,code',
        'profile_id' => 'required|nullable|exists:books_profile_profiles,id',
        'is_activated' => 'sometimes|nullable|boolean',
        'user_id' => 'sometimes|nullable|integer|exists:users,id',
        'used_by_profile_id' => 'sometimes|nullable|integer|exists:books_profile_profiles,id',
        'activated_at' => 'sometimes|nullable|date',
        'expire_in' => 'sometimes|nullable|date',
    ];

    public $belongsTo = [
        'user' => User::class,
        'book' => Book::class,
        'profile' => Profile::class,
        'activated_by' => [Profile::class, 'key' => 'used_by_profile_id', 'otherKey' => 'id'],
    ];

    /**
     * @var array
     */
    public $morphTo = [
        'promoable' => []
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($promocode) {
            $promocode->uniqueCodeGenerator();
        });
    }

    /**
     * Генерирует уникальный код для данного объекта.
     *
     * Этот метод генерирует уникальный код, вызывая статический метод `gen()`
     * и проверяет, присутствует ли уже сгенерированный код в базе данных.
     * Он пытается сгенерировать уникальный код максимум 10 раз.
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

        throw new Exception('Не удалось сгенерировать уникальный код после ' . self::MAX_ATTEMPTS . ' попыток');
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
        $randomString = Str::random(self::CODE_LENGTH);

        return strtoupper(hash('xxh32', $timestamp . $randomString));
    }

    public function scopeAlive(Builder $builder)
    {
        return $builder->notActivated()->notExpired();
    }

    public function scopeCurrentMonthCreated(Builder $builder): Builder
    {
        return $builder->where('created_at', '>=', Carbon::now()->startOfMonth());
    }

    public function scopeNotActivated(Builder $builder)
    {
        return $builder->where('is_activated', false);
//            ->whereDate('expire_in', '>', today()); не выберет с expire_in = null; и сломает prunable()
    }

    public function scopeNotExpired(Builder $builder): Builder
    {
        return $builder->where(
            fn($q) => $q->whereDate('expire_in', '>', today())->orWhereNull('expire_in')
        );
    }

    public function scopeBook(Builder $builder, Book $book): Builder
    {
        return $builder->where('book_id', $book->id);
    }

    public function scopeCode(Builder $builder, string $code): Builder
    {
        return $builder->where('code', $code);
    }

    public function scopeExpired(Builder $builder): Builder
    {
        return $builder->whereDate('expire_in', '<=', today());
    }

    public function prunable(): Builder
    {
        return static::query()->notActivated()->expired();
    }
}
