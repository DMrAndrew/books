<?php namespace Books\Orders\Models;

use Model;

/**
 * BalanceDeposit Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class BalanceDeposit extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_orders_balance_deposits';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'amount' => 'required|integer|min:1'
    ];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'amount',
    ];

    public $morphMany = [
        'products' => [
            OrderProduct::class,
            'name' => 'orderable',
        ],
    ];
}
