<?php namespace Books\Withdrawal\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * Withdrawal Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Withdrawal extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_withdrawal_withdrawals';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'user_id' => 'required|exists:users,id',
        'amount' => 'required',
        'date' => 'required',
    ];

    public $fillable = [
        'user_id',
        'amount',
        'date',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'date',
    ];

    /**
     * @var array
     */
    public $belongsTo = [
        'user' => [User::class],
    ];
}
