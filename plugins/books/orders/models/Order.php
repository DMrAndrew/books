<?php

namespace Books\Orders\Models;

use Books\Orders\Classes\Enums\OrderStatusEnum;
use Model;
use RainLab\User\Models\User;

/**
 * Order Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Order extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_orders_orders';

    public $rules = [
        'user_id' => 'required|integer',
        'status' => 'integer',
        'amount' => 'sometimes|integer|min:0',
    ];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'user_id',
        'status',
        'amount',
    ];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * @var string[]
     */
    protected $enums = [
        'status' => OrderStatusEnum::class,
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'type' => OrderStatusEnum::class,
    ];

    /**
     * @var array
     */
    public $belongsTo = [
        'user' => [
            User::class,
        ],
    ];

    /**
     * @var array hasMany relationship
     */
    public $hasMany = [
        'products' => [OrderProduct::class],
        'awards' => [
            OrderProduct::class,
            'scope' => 'awards',
        ],
        'donations' => [
            OrderProduct::class,
            'scope' => 'donations',
        ],
        'deposits' => [
            OrderProduct::class,
            'scope' => 'deposits',
        ],
        'promocodes' => [OrderPromocode::class],
    ];

    /**
     * @param $query
     * @param OrderStatusEnum $status
     * @return void
     */
    public function scopeWhereStatus($query, OrderStatusEnum $status): void
    {
        $query->where('status', $status->value);
    }
}
