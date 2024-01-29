<?php

declare(strict_types=1);

namespace Books\Shop\Models;

use Model;
use October\Rain\Database\Traits\SimpleTree;
use October\Rain\Database\Traits\Validation;

/**
 * Category Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Category extends Model
{
    use SimpleTree, Validation;

    /**
     * @var string table name
     */
    public $table = 'books_shop_categories';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'required',
    ];

    /**
     * @var \string[][]
     */
    public $hasMany = [
        'products' => [
            Product::class,
            'key' => 'id',
            'otherKey' => 'category_id',
        ],
    ];
}
