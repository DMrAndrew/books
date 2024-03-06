<?php namespace Books\Shop\Models;

use Model;

/**
 * OrderItems Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class OrderItems extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_shop_order_items';

    /**
     * @var array rules for validation
     */
    public $rules = [];
}
