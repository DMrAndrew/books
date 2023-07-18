<?php namespace Books\Wallet\Models;

use Model;
use RainLab\User\Models\User;

/**
 * Fake Wallet Model to use bavix package in backend controller
 * original model: Bavix\Wallet\Models\Wallet
 */
class Wallet extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'wallets';

    protected $guarded = ['*'];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    /**
     * @var string[][]
     */
    public $belongsTo = [
        'user' => [User::class, 'key' => 'holder_id', 'otherKey' => 'id'],
    ];
}
