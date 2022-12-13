<?php namespace Books\User\Models;

use Books\User\Classes\BookUserSettingsEnum;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;

/**
 * AccountSettings Model
 *
 * @property BookUserSettingsEnum $declaration
 */
class BookUserSettings extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_user_settings';

    protected $appends = ['declaration'];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('accountable', function (Builder $builder) {
            $builder->whereIn('setting_id', collect(BookUserSettingsEnum::accountable())->pluck('value')->toArray());
        });
    }

    public function getDeclarationAttribute(): ?BookUserSettingsEnum
    {
        return BookUserSettingsEnum::tryFrom($this->setting_id);
    }


}
