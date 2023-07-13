<?php namespace Books\Book\Models;

use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Enums\SellStatisticSellTypeEnum;
use Books\Profile\Models\Profile;
use Model;
use October\Rain\Database\Traits\Validation;

/**
 * SellStatistics Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 *
 * @method \October\Rain\Database\Relations\BelongsTo profile
 * @method \October\Rain\Database\Relations\BelongsTo edition
 * @property Edition edition
 * @property Profile profile
 */
class SellStatistics extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_sell_statistics';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'profile_id' => 'required|exists:books_profile_profiles,id',
        'edition_id' => 'required',
        'edition_type' => 'required',
        'sell_at' => 'required',
        'edition_status' => 'required',
        'sell_type' => 'required',
        'price' => 'required',
        'reward_rate' => 'required',
        'reward_value' => 'required',
        'tax_rate' => 'required',
        'tax_value' => 'required',
    ];

    public $fillable = [
        'profile_id',
        'edition_id',
        'edition_type',
        'sell_at',
        'edition_status',
        'sell_type',
        'price',
        'reward_rate',
        'reward_value',
        'tax_rate',
        'tax_value',
    ];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'sell_at',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'edition_type' => EditionsEnums::class,
        'edition_status' => BookStatus::class,
        'sell_type' => SellStatisticSellTypeEnum::class,
    ];

    /**
     * @var array
     */
    public $belongsTo = [
        'profile' => [Profile::class],
        'edition' => [Edition::class, 'key' => 'edition_id'],
    ];

    protected function afterCreate()
    {
        $this->edition?->book?->refreshAllowedVisits();
    }

}
