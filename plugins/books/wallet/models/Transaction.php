<?php namespace Books\Wallet\Models;

use Model;
use RainLab\User\Models\User;

/**
 * Fake Transaction Model to use bavix package in backend controller
 * original model: Bavix\Wallet\Models\Transaction
 */
class Transaction extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'transactions';

    protected $guarded = ['*'];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    /**
     * @var string[][]
     */
    public $belongsTo = [
        'user' => [User::class, 'key' => 'payable_id', 'otherKey' => 'id'],
    ];

    /**
     * @return string
     */
    public function getTypeTranslatedAttribute(): string
    {
        return match ($this->type) {
            'withdraw' => 'Списание',
            'deposit' => 'Зачисление',
            default => $this->type,
        };
    }
}
