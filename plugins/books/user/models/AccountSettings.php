<?php

namespace Books\User\Models;

use Books\User\Classes\UserSettingsEnum;
use October\Rain\Database\Builder;

/**
 * AccountSettings Model
 *
 * @property UserSettingsEnum $declaration
 */
class AccountSettings extends Settings
{
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('accountable', function (Builder $builder) {
            $builder->whereIn('setting_id', collect(UserSettingsEnum::accountable())->pluck('value')->toArray());
        });
    }
}
