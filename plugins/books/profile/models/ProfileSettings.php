<?php namespace Books\Profile\Models;

use Books\User\Classes\BookUserSettingsEnum;
use Books\User\Models\BookUserSettings;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;

/**
 * ProfileSettings Model
 *
 * @property BookUserSettingsEnum $declaration
 */
class ProfileSettings extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_user_settings';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    protected $fillable = ['setting_id','value','user_id'];


    protected $appends = ['declaration'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        static::addGlobalScope('profilable', function (Builder $builder) {
            $builder->whereIn('setting_id', collect(BookUserSettingsEnum::profilable())->pluck('value')->toArray());
        });
    }

    public static function fromEnum(BookUserSettingsEnum $enum): static
    {
        return new static([
            'setting_id' => $enum->value,
            'value' => null
        ]);
    }
    public function getDeclarationAttribute(): ?BookUserSettingsEnum
    {
        return BookUserSettingsEnum::tryFrom($this->setting_id);
    }
}
