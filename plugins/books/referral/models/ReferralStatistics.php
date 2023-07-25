<?php namespace Books\Referral\Models;

use Books\Orders\Models\Order;
use Model;
use RainLab\User\Models\User;

/**
 * ReferralStatistic Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class ReferralStatistics extends Model
{
    /**
     * @var string table name
     */
    public $table = 'books_referral_statistics';

    /**
     * @var array
     */
    public $rules = [
        'user_id' => 'required|exists:users,id',
        'referrer_id' => 'required|books_referral_referrers:users,id',
        'order_id' => 'required|books_orders_orders:users,id',
        'sell_at' => 'required',
        'price' => 'required',
        'reward_rate' => 'required',
        'reward_value' => 'required',
    ];

    /**
     * @var array
     */
    public $fillable = [
        'user_id', // statistic owner
        'referrer_id',
        'order_id',
        'sell_at',
        'price',
        'reward_rate',
        'reward_value',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'sell_at',
    ];

    /**
     * @var array
     */
    public $belongsTo = [
        'user' => [User::class],
        'referrer' => [Referrer::class],
        'order' => [Order::class],
    ];
}
