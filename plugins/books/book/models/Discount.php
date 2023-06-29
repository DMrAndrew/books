<?php namespace Books\Book\Models;

use App;
use Books\Book\Classes\PriceTag;
use Carbon\Carbon;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use ValidationException;

/**
 * Discount Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Discount extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_discounts';

    protected $fillable = ['amount', 'active_at'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'amount' => 'required|integer|min:1|max:100',
        'active_at' => 'required|date'
    ];

    protected $casts = ['amount' => 'integer'];

    protected $dates = ['active_at'];

    public $belongsTo = ['edition' => [Edition::class]];

    /**
     * @throws ValidationException
     */
    public function setActiveAtAttribute($date): void
    {
        if (!$date) {
            throw new ValidationException(['discount' => 'Укажите дату скидки.']);
        }
        $date = Carbon::parse($date)->startOfDay();

        if (App::environment() === 'production') {
            if (!$date->gt(today()->endOfDay())) {
                throw new ValidationException(['discount' => 'Скидку можно установить только с завтрашнего дня.']);
            }
        } else {
            // для тестирования скидку можно установить сегодняшним днем
            if (!$date->gt(today()->subDay()->endOfDay())) {
                throw new ValidationException(['discount' => 'Скидку можно установить только с сегодняшнего дня.']);
            }
        }

        $this->attributes['active_at'] = $date;
    }

    public function priceTag(): PriceTag
    {
        return new PriceTag(edition: $this->edition, discount: $this);
    }

    public function isActive()
    {
        return $this->active_at->isSameDay(today());
    }

    public function scopeActive(Builder $builder, ?Carbon $date = null): Builder
    {
        $date ??= today();
        return $builder->whereDate('active_at', '=', $date);
    }

    public function scopeAlreadySetInMonth(Builder $builder, Carbon $carbon): Builder
    {
        return $builder->whereMonth('active_at', '=', $carbon);
    }
}
