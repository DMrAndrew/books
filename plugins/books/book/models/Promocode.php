<?php declare(strict_types=1);

namespace Books\Book\Models;

use Books\Book\Classes\CodeGenerator;
use Books\Profile\Models\Profile;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

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
        'book_id',
        'profile_id',
        'is_activated',
        'user_id',
        'activated_at',
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'code' => 'missing|unique:books_book_promocodes,code',
        'book_id' => 'required|nullable|exists:books_book_books,id',
        'profile_id' => 'required|nullable|exists:books_profile_profiles,id',
        'is_activated' => 'sometimes|nullable|boolean',
        'user_id' => 'sometimes|nullable|integer|exists:users,id',
        'activated_at' => 'sometimes|nullable|date',
    ];

    public $belongsTo = [
        'user' => User::class,
        'book' => Book::class,
        'profile' => Profile::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($promocode) {
            $promocode->code = self::generateUniqueCode();
        });
    }

    public static function generateUniqueCode(): string
    {
        return CodeGenerator::generateUniqueCode((new self())->getTable(), self::CODE_LENGTH);
    }

    public function scopeNotActivated(Builder $builder)
    {
        return $builder->where('is_activated', false);
    }
}
