<?php namespace Books\Payment\Models;

use Books\Payment\Classes\Enums\PaymentStatusEnum;
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
        'payment_status' => 'integer',
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
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'payment_status' => PaymentStatusEnum::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($payment) {
            $payment->payment_id = Str::uuid();
        });
    }

    /**
     * @var array
     */
    public $belongsTo = [
        'user' => [
            User::class,
            'payer_id',
        ],
    ];

    /**
     * @param $query
     * @param PaymentStatusEnum $status
     * @return void
     */
    public function scopeWhereStatus($query, PaymentStatusEnum $status): void
    {
        $query->where('status', $status->value);
    }
}
