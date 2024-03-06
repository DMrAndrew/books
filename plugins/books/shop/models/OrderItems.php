<?php namespace Books\Shop\Models;

use Books\Profile\Models\Profile;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'product_id',
        'quantity',
        'price',
    ];

    protected $with = [
        'seller',
        'buyer',
        'product',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'seller_id', 'id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'buyer_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
