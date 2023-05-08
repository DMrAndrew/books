<?php namespace Books\Book\Models;

use Books\Orders\Models\OrderProduct;
use Model;

/**
 * Donation Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Donation extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_donations';

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
