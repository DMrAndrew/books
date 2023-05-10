<?php

namespace Books\Orders\Models;

use Books\Book\Models\Promocode;
use Model;

/**
 * OrderPromocodes Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class OrderPromocode extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_orders_promocodes';

    public $rules = [
        'order_id' => 'required|integer',
        'promocode_id' => 'required|integer',
    ];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'order_id',
        'promocode_id',
    ];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    public $belongsTo = [
        'order' => [
            Order::class,
        ],
        'promocode' => [
            Promocode::class,
        ],
    ];
}
