<?php namespace Books\User\Models;


use Books\User\Classes\UserSettingsEnum;
use Books\User\Classes\SettingsTagEnum;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;

/**
 * AccountSettings Model
 *
 * @property UserSettingsEnum $declaration
 */
class Settings extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_user_settings';

    protected $appends = ['declaration'];

    protected $fillable = ['setting_id', 'value', 'user_id'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'user_id' => 'required|exists:users,id',
        'setting_id' => 'required|integer',
        'value' => 'required',
    ];

    public function getDeclarationAttribute(): ?UserSettingsEnum
    {
        return UserSettingsEnum::tryFrom($this->setting_id);
    }

    public static function fromEnum(UserSettingsEnum $enum): static
    {
        return new static([
            'setting_id' => $enum->value,
            'value' => $enum->defaultValue()
        ]);
    }

    public function hasTag(SettingsTagEnum $tag): bool
    {
        return $this->declaration->tag() === $tag;
    }


}
