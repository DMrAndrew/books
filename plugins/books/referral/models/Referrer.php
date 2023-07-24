<?php namespace Books\Referral\Models;

use Exception;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * Referrer Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Referrer extends Model
{
    use Validation;

    const REFERRAL_PARTNER_CODE_LENGTH = 6;
    const MAX_CREATE_CODE_ATTEMPTS = 10;

    /**
     * @var string table name
     */
    protected $table = 'books_referral_referrers';

    /**
     * @var array
     */
    public $fillable = [
        'code',
        'target_link',
        'user_id',
    ];

    /**
     * @var array
     */
    public $rules = [
        'code' => 'string|unique:books_referral_referrers,code',
        'target_link' => 'required|string',
        'user_id' => 'required|integer',
    ];

    public $belongsTo = [
        'user' => User::class,
    ];

    public $hasMany = [
        'referrals' => Referrals::class,
        'visits' => ReferralVisit::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($referrer) {
            $referrer->checkReferrerRequirements();
            $referrer->uniqueCodeGenerator();
        });
    }

    /**
     * Генерирует уникальный код партнера
     *
     * @return void
     * @throws Exception
     */
    public function uniqueCodeGenerator(): void
    {
        for ($i = 0; $i < self::MAX_CREATE_CODE_ATTEMPTS; $i++) {
            $code = static::gen();
            if (!static::query()->code($code)->exists()) {
                $this->attributes['code'] = $code;
                return;
            }
        }

        throw new Exception('Не удалось сгенерировать код партнера после ' . self::MAX_CREATE_CODE_ATTEMPTS . ' попыток');
    }

    /**
     * Генерирует уникальную строку (для ссылки партнера реферальной программы)
     *
     * @param int $length
     *
     * @return string
     * @throws Exception
     */
    public static function gen(int $length = self::REFERRAL_PARTNER_CODE_LENGTH): string
    {
        // из чисел
        $characters = '0123456789';

        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function checkReferrerRequirements(): void
    {
        $minAge = 18;
        $userAdulthood = $this->user->birthday->addYears($minAge);
        if ($userAdulthood->greaterThan(now())) {
            throw new Exception("Минимальный возраст для участия в реферальной программе составляет {$minAge} лет");
        }
    }

    /**
     * @param Builder $builder
     * @param string $code
     *
     * @return Builder
     */
    public function scopeCode(Builder $builder, string $code): Builder
    {
        return $builder->where('code', $code);
    }
}
