<?php namespace Books\Shop\Models;

use Model;
use RainLab\User\Models\User;

/**
 * Product Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Product extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_shop_products';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $belongsTo = ['seller' => User::class,'key'=>'id','otherKey' => 'seller_id'];
}
