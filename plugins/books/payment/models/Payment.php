<?php namespace Books\Payment\Models;

use Books\Orders\Models\Order;
use Illuminate\Support\Str;
use Model;
use RainLab\User\Models\User;

/**
 * Payment Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Payment extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public const CURRENCY = 'RUB';

    /**
     * @var string table name
     */
    public $table = 'books_payment_payments';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'order_id' => 'sometimes|nullable|integer',
        'payer_id' => 'required|integer',
        'payer_email' => 'required|email',
        'amount' => 'required|integer|min:1',
        'currency' => 'required|string',
        'payment_status' => 'string', // from payment service
        'transaction_id' => 'string', // from payment service
    ];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'order_id',
        'payer_id',
        'payer_email',
        'amount',
        'currency',
        'payment_status',
        'transaction_id',
    ];

    protected function beforeCreate()
    {
        $this->payment_id = Str::uuid();
    }

    /**
     * @var array
     */
    public $belongsTo = [
        'user' => [
            User::class,
            'payer_id',
        ],
        'order' => [
            Order::class,
        ],
    ];
}
