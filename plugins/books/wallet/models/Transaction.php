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

    /**
     * @return string|null
     */
    public function getMetaFormattedAttribute(): ?string
    {
        if ($this->meta == null) {
            return null;
        }

        // if json string
        $metaFromJson = json_decode(html_entity_decode($this->meta), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $metaString = [];
            foreach ($metaFromJson as $key => $value) {
                $metaString[] = $key . ': ' . $value;
            }
            return implode('; ', $metaString);
        }

        // else string
        return $this->meta;
    }
}
