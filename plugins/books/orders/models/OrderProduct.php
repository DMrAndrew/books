<?php

namespace Books\Orders\Models;

use Books\Book\Models\Edition;
use Books\Orders\Classes\Enums\OrderStatusEnum;
use Model;
use RainLab\User\Models\User;

/**
 * OrderProduct Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class OrderProduct extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_orders_products';

    public $rules = [
        'order_id' => 'required|integer',
    ];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'order_id',
    ];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at',
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
        'order' => [
            Order::class,
        ],
    ];

    /**
     * @var array
     */
    public $morphTo = [
        'orderable' => []
    ];
}
