<?php

namespace Books\Orders\Models;

use Books\Book\Models\Award;
use Books\Book\Models\Donation;
use Books\Book\Models\Edition;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;

/**
 * OrderProduct Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class OrderProduct extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_orders_products';

    public $rules = [
        'order_id' => 'required|integer',
        'initial_price' => 'sometimes|integer',
        'amount' => 'sometimes|integer',
    ];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'order_id',
        'initial_price',
        'amount',
        'book_id',
    ];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    public $belongsTo = [
        'order' => [
            Order::class,
        ],
    ];

    /**
     * @var array
     */
    public $morphTo = [
        'orderable' => [],
    ];

    public function scopeAwards(Builder $builder)
    {
        return $builder->type(Award::class);
    }

    public function scopeDonations(Builder $builder)
    {
        return $builder->type(Donation::class);
    }

    public function scopeDeposits(Builder $builder)
    {
        return $builder->type(BalanceDeposit::class);
    }

    public function scopeEditions(Builder $builder)
    {
        return $builder->type(Edition::class);
    }

    public function scopeType(Builder $builder, string $class): Builder
    {
        return $builder->where('orderable_type', $class);
    }

    public function isPromocodeApplied(): bool
    {
        $orderProduct = $this;
        $product = $orderProduct->orderable;

        return $orderProduct->order->promocodes()->whereHas('promocode', function ($q) use ($product){
            $q->where('promoable_id', $product->id);
        })->exists();
    }
}
