<?php namespace Books\Shop\Models;

use Model;
use October\Rain\Database\Relations\HasMany;

/**
 * Order Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Order extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_shop_orders';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'full_name' => 'required|string|min:3',
        'phone' => 'required|string|regex:/^\+7-\d{3}-\d{3}-\d{2}-\d{2}$/i',
        'country_id' => 'required',
        'index' => 'required|string|min:3',
        'address' => 'required|string|min:3',
        'amount' => 'required|int|gt:0',
    ];

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'full_name',
        'phone',
        'country_id',
        'index',
        'address',
        'amount',
    ];

    /**
     * @var array|string[]
     */
    public array $attributeNames = [
        'full_name' => 'Имя и фамилия',
        'phone' => 'Номер телефона',
        'country_id' => 'Страна',
        'index' => 'Индекс',
        'address' => 'Адрес',
        'amount' => 'Сумма',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(OrderItems::class, 'order_id', 'id');
    }
}
