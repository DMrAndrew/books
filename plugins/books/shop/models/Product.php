<?php

declare(strict_types=1);

namespace Books\Shop\Models;

use Books\Profile\Models\Profile;
use Model;
use October\Rain\Database\Traits\Validation;
use System\Models\File;

/**
 * Product Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Product extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_shop_products';

    /**
     * @var string[]
     */
    public $fillable = [
        'title',
        'description',
        'price',
        'quantity',
        'category_id',
        'seller_id',
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'required|string|min:3',
        'description' => 'required|string|min:10',
        'price' => 'required|integer|min:50',
        'quantity' => 'required|integer',
        'category_id' => 'required|exists:books_shop_categories,id',
    ];

    /**
     * @var array|string[]
     */
    public array $customMessages = [
        'title.required' => 'Название товара обязательно для заполнения',
        'description.required' => 'Описание товара обязательно для заполнения',
        'price.required' => 'Укажите цену товара',
        'quantity.required' => 'Укажите категорию товара',
        'category_id.required' => 'Укажите категорию товара',
        'upload_image.accepted' => 'Добавьте изображение товара',
    ];

    /**
     * @var array|string[]
     */
    public array $attributeNames = [
        'title' => 'Название товара',
        'description' => 'Описание товара',
        'price' => 'Цена',
        'quantity' => 'Количество',
        'category_id' => 'Категория',
    ];

    /**
     * @var \string[][]
     */
    public $belongsTo = [
        'seller' => [Profile::class, 'key' => 'seller_id', 'otherKey' => 'id'],
        'category' => [Category::class, 'key' => 'category_id', 'otherKey' => 'id'],
    ];

    /**
     * @var array[]
     */
    public $attachOne = [
        'product_image' => [File::class],
    ];
}
