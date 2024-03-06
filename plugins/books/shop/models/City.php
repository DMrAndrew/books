<?php namespace Books\Shop\Models;

use Model;

/**
 * City Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class City extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_shop_cities';

    /**
     * @var array rules for validation
     */
    public $rules = [];
}
