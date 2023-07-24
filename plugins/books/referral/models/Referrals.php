<?php namespace Books\Referral\Models;

use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * Referrals Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Referrals extends Model
{
    use Validation;

    const COOKIE_LIVE_TIME_DAYS = 14;
    const REFERRAL_LIVE_TIME_DAYS = 14;

    /**
     * @var string table name
     */
    protected $table = 'books_referral_referrals';

    /**
     * @var array
     */
    public $fillable = [
        'user_id',
        'referrer_id',
        'valid_till'
    ];

    /**
     * @var array
     */
    public $rules = [];

    /**
     * @var array
     */
    protected $dates = ['valid_till'];

    public $belongsTo = [
        'user' => User::class,
        'referrer' => Referrer::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($referral) {
            $referral->setValidTill();
        });
    }

    /**
     * @return void
     */
    public function setValidTill(): void
    {
        if (!isset($this->attributes['valid_till']) || $this->attributes['valid_till'] == null) {
            $this->attributes['valid_till'] = now()->addDays(self::REFERRAL_LIVE_TIME_DAYS);
        }

        return;
    }

    /**
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeActive(Builder $builder): Builder
    {
        return $this->where('valid_till', '>=', now());
    }
}
