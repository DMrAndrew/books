<?php namespace Books\Shop\Models;

use Model;
use October\Rain\Database\Traits\SimpleTree;

/**
 * Category Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\Validation, SimpleTree;

    const PARENT_ID = 'parent_id';

    /**
     * @var string table name
     */
    public $table = 'books_shop_categories';

    /**
     * @var array rules for validation
     */
    public $rules = [

    ];
}
