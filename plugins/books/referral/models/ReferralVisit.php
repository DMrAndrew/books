<?php namespace Books\Referral\Models;

use Model;
use October\Rain\Database\Traits\Validation;

/**
 * ReferralVisit Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class ReferralVisit extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_referral_referral_visits';

    /**
     * @var array
     */
    public $fillable = [
        'referrer_id',
    ];

    /**
     * @var array
     */
    public $rules = [];

    /**
     * @var array
     */
    public $belongsTo = [
        'referrer' => Referrer::class,
    ];
}
