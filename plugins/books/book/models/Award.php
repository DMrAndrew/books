<?php namespace Books\Book\Models;

use Books\Book\Classes\Enums\AwardsEnum;
use Books\Orders\Models\OrderProduct;
use Model;
use October\Rain\Database\Traits\Validation;

/**
 * Award Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Award extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_awards';

    protected $casts = ['type' => AwardsEnum::class];

    protected $fillable = ['name', 'rate', 'price', 'type'];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $morphMany = [
        'products' => [
            OrderProduct::class,
            'name' => 'orderable',
        ],
    ];
}
