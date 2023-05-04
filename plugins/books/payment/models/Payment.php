<?php namespace Books\Payment\Models;

use Model;

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
    public $rules = [];
}
