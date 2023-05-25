<?php namespace Books\Book\Models;

use Books\Catalog\Models\Genre;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use RainLab\Location\Models\Country;

/**
 * Prohibited Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Prohibited extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_prohibiteds';

    protected $fillable = ['is_allowed', 'country_id'];

    public const LIST = [Genre::class => [
        'class' => Genre::class,
        'label' => 'Жанр',
        'name' => 'genre',
        'label_field' => 'name'
    ], Book::class => [
        'class' => Book::class,
        'label' => 'Книга',
        'name' => 'book',
        'label_field' => 'title'
    ]];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $belongsTo = [
        'country' => [Country::class]
    ];

    public $morphTo = ['prohibitable' => []];

    public function scopeType(Builder $builder, string $class): Builder
    {
        return $builder->where('prohibitable_type', $class);
    }

    //TODO to dynamic scope
    public function scopeBooks(Builder $builder): Builder
    {
        return $builder->where('prohibitable_type', Book::class);
    }

    //TODO to dynamic scope
    public function scopeGenres(Builder $builder): Builder
    {
        return $builder->where('prohibitable_type', Genre::class);
    }

    public function scopeExceptAtCountry(Builder $builder, Country $country): Builder
    {
        if (isComDomainRequested()) {
            // Исключить запрещённые в выбранной стране или разрешённые в других странах
            return $builder->country($country)->notAllowed()
                ->orWhere(fn($b) => $b->countryNot($country)->allowed());
        }
        // не исключать разрешённые (в любой стране) или запрещённые в других странах
        return $builder->whereNot(fn($query) => $query->allowed()
            ->orWhere(fn($b) => $b->countryNot($country)->notAllowed()));
    }

    public function scopeIds(Builder $builder)
    {
        return $builder->pluck('prohibitable_id');
    }

    public function scopeCountry(Builder $builder, Country $country): Builder
    {
        return $builder->where('country_id', $country->id);
    }

    public function scopeCountryNot(Builder $builder, Country $country): Builder
    {
        return $builder->where('country_id', '!=', $country->id);
    }

    public function scopeIsAllowed(Builder $builder, bool $is_allowed = true): Builder
    {
        return $builder->where('is_allowed', $is_allowed);
    }

    public function scopeNotAllowed(Builder $builder): Builder
    {
        return $builder->isAllowed(false);
    }

    public function scopeAllowed(Builder $builder): Builder
    {
        return $builder->isAllowed(true);
    }

    public function getProhibitableTypeOptions()
    {
        return array_combine(array_keys(static::LIST), array_pluck(static::LIST, 'label'));
    }

    public function getProhibitableIdOptions()
    {
        $class = $this->prohibitable_type ?? Genre::class;
        $column = static::LIST[$class]['label_field'];
        return $class::query()->orderBy($column)->lists($column, 'id');
    }

    public function getIsAllowedOptions()
    {
        return [false => 'Запрещено', true => 'Разрешено'];
    }

    public function getContentTypeAttribute()
    {
        return static::LIST[$this->prohibitable_type]['label'];
    }

    public function getContentLabelAttribute()
    {
        return $this->prohibitable?->{static::LIST[$this->prohibitable_type]['label_field']} ?? '';
    }
}
