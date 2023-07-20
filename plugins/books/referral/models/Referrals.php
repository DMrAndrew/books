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

    /**
     * @var string table name
     */
    public $table = 'books_referral_referrals';

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
        'referrer_id' => Referrer::class,
    ];

    /**
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeActive(Builder $builder): Builder
    {
        return $this->where('valid_till', '<=', now());
    }
}
