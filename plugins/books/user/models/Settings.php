<?php

namespace Books\User\Models;


use Books\User\Classes\BoolOptionsEnum;
use Books\User\Classes\PrivacySettingsEnum;
use Books\User\Classes\UserSettingsEnum;
use October\Rain\Database\Builder;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * AccountSettings Model
 *
 * @property UserSettingsEnum $type
 */
class Settings extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_user_settings';

    protected $fillable = ['type', 'value', 'user_id'];

    protected $casts = [
        'type' => UserSettingsEnum::class
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'user_id' => 'required|exists:users,id',
        'type' => 'required|integer',
    ];

    public function scopePrivacy(Builder $builder)
    {
        return $builder->type(...UserSettingsEnum::privacy());
    }

    public function scopeNotify(Builder $builder)
    {
        return $builder->type(...UserSettingsEnum::notify());
    }

    public function scopeType(Builder $builder, ?UserSettingsEnum ...$types): Builder
    {
        return $builder->whereIn('type', collect($types)->pluck('value')->toArray());
    }

    public function scopeValue(Builder $builder, BoolOptionsEnum|PrivacySettingsEnum ...$types): Builder
    {
        return $builder->whereIn('value', collect($types)->pluck('value')->toArray());
    }


    public function scopeUser(Builder $builder, User $user): Builder
    {
        return $builder->where('user_id', '=', $user->id);
    }


    public function isAccountable(): bool
    {
        return $this->type->isAccountable();
    }

}
