<?php namespace Books\Book\Models;

use Books\Book\Classes\CodeGenerator;
use Model;
use October\Rain\Database\Traits\Validation;

/**
 * Promocode Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
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
        'code' => 'required|unique:users,email_address',
        'book_id' => 'required|nullable|exists:books_book_books,id',
        'profile_id' => 'required|nullable|exists:books_profile_profiles,id',
        'is_activated' => 'sometimes|nullable|boolean',
        'user_id' => 'sometimes|nullable|exists:users,id',
        'activated_at' => 'sometimes|nullable|date',
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
}
