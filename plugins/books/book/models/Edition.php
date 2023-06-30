<?php

namespace Books\Book\Models;

use Books\Book\Classes\EditionService;
use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\ChapterSalesType;
use Books\Book\Classes\Enums\ChapterStatus;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Enums\ElectronicFormats;
use Books\Book\Classes\PriceTag;
use Books\Book\Jobs\ParseFB2;
use Books\Orders\Classes\Contracts\ProductInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Db;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Relations\MorphMany;
use October\Rain\Database\Traits\Revisionable;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;
use System\Models\File;
use System\Models\Revision;

/**
 * Edition Model
 *
 * * @method HasMany chapters
 * * @method HasMany discounts
 * * @method HasMany sells Записи из статистики коммерческого кабинета, т.е. только те, где книга куплена за деньги
 * * @method AttachOne fb2
 * * @method BelongsTo book
 * * @method MorphMany customers Аккаунты, у которых есть книга (включая покупки по промокоду)
 *
 * * @property  Book book
 * * @property  BookStatus status
 * * @property  EditionsEnums type
 * * @property  bool download_allowed
 */
class Edition extends Model implements ProductInterface
{
    use Validation;
    use SoftDelete;
    use Revisionable;

    const UPDATE_CHUNK_LENGTH = 4999;

    /**
     * @var string table name
     */
    public $table = 'books_book_editions';

    protected $revisionable = ['length', 'status', 'price'];

    public $revisionableLimit = 10000;

    public bool $forceRevision = false;

    public string $trackerChildRelation = 'chapters';

    protected $fillable = [
        'type',
        'download_allowed',
        'comment_allowed',
        //        'sales_free',
        'free_parts',
        'status',
        'price',
        'book_id',
        'length',
    ];

    protected $casts = [
        'type' => EditionsEnums::class,
        'free_parts' => 'integer',
        'price' => 'integer',
        //        'sales_free' => 'boolean',
        'download_allowed' => 'boolean',
        'comment_allowed' => 'boolean',
        'status' => BookStatus::class,
    ];

    protected $dates = ['sales_at'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'type' => 'required|integer',
        'price' => 'filled|integer|min:0|max:9999',
        'free_parts' => 'filled|integer',
        'download_allowed' => 'boolean',
        'comment_allowed' => 'boolean',
        'fb2' => ['nullable', 'file', 'mimes:xml', 'max:30720'],
    ];

    public $hasMany = [
        'chapters' => [Chapter::class, 'key' => 'edition_id', 'otherKey' => 'id'],
        'discounts' => [Discount::class, 'key' => 'edition_id', 'otherKey' => 'id'],
        'sells' => [SellStatistics::class, 'key' => 'edition_id', 'otherKey' => 'id'],
    ];

    public $attachOne = [
        'fb2' => File::class,
        'epub' => File::class,
        'mobi' => File::class,
    ];

    public $belongsTo = [
        'book' => [Book::class, 'key' => 'book_id', 'otherKey' => 'id'],
    ];

    public $morphMany = [
        'revision_history' => [Revision::class, 'name' => 'revisionable'],
        'promocodes' => [
            Promocode::class,
            'name' => 'promoable',
        ],
        'customers' => [
            UserBook::class,
            'name' => 'ownable',
        ],
    ];

    public function products(): MorphMany
    {
        return $this->morphMany(UserBook::class, 'ownable');
    }

    public function discount()
    {
        return $this->hasOne(Discount::class, 'edition_id', 'id')->whereDate('active_at', '=', today());
    }

    public function service(): EditionService
    {
        return new EditionService($this);
    }

    public function allowedDownload(ElectronicFormats $format = ElectronicFormats::FB2): bool
    {
        return $this->download_allowed
            && $this->hasRelation($format->value)
            && $this->{$format->value};
    }

    public function getLastUpdatedAtAttribute()
    {
        return $this->revision_history()->where('field', '=', 'length')->orderByDesc('created_at')->first()?->created_at;
    }

    public function getUpdateHistoryAttribute()
    {
        $items = $this->revision_history()
            ->where('field', '=', 'length')
            ->get()
            ->chunkWhile(function ($value, $key, $chunk) {
                return (int)$chunk->sum('odds') <= self::UPDATE_CHUNK_LENGTH;
            })
            ->filter(fn($i) => $i->sum('odds') >= self::UPDATE_CHUNK_LENGTH)->map(function ($collection) {
                return [
                    'date' => $collection->last()->created_at,
                    'value' => (int)$collection->sum('odds'),
                    'new_value' => (int)$collection->last()->new_value,
                ];
            })
            ->reverse();

        $count = $items->count();
        $days = $count ? CarbonPeriod::create($items->last()['date'], $items->first()['date'])->count() : 0;

        $freq_string = $count ? getFreqString($count, $days) : '';
        $freq = $count ? $count / $days : 0;

        return [
            'freq' => $freq,
            'freq_string' => $freq_string,
            'items' => $items,
            'count' => $count,
            'days' => $days,
        ];
    }

    public function priceTag(): PriceTag
    {
        return new PriceTag($this, $this->discount);
    }

    public function scopeWithActiveDiscountExist(Builder $builder): Builder
    {
        return $builder->withExists('discount');
    }

    public function scopeWithFiles(Builder $builder): Builder
    {
        return $builder->with(ElectronicFormats::FB2->value);
    }

    public function scopeActiveDiscountExist(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->has('discount');
    }

    public function getAllowedForDiscountAttribute(): bool
    {
        return in_array($this->getOriginal('status'), [BookStatus::COMPLETE, BookStatus::WORKING]) && !$this->isFree();
    }

    public function scopeAllowedForDiscount(Builder $builder)
    {
        return $builder->status(BookStatus::COMPLETE, BookStatus::WORKING)->free(false);
    }

    public function isSold(?User $user): bool
    {
        return $user && $this->customers()->user($user)->exists();
    }

    public function getSoldCountAttribute(): int
    {
        return $this->sells()->count();
    }

    public function scopeWithSellsCount(Builder $builder)
    {
        return $builder->withCount('sells');
    }

    public function editAllowed(): bool
    {
        return !$this->isPublished()
            || $this->isFree()
            || in_array($this->getOriginal('status'), [BookStatus::WORKING, BookStatus::FROZEN])
            || ($this->getOriginal('status') === BookStatus::HIDDEN && !$this->hadCompleted());
    }

    public function isFree(): bool
    {
        return !!!(int)$this->getOriginal('price');
    }

    public function hadCompleted()
    {
        return $this->revision_history()->where(['field' => 'status', 'old_value' => BookStatus::COMPLETE->value])->exists();
    }

    public function isPublished(): bool
    {
        return (bool)$this->getOriginal('sales_at');
    }

    public function setPublishAt()
    {
        $this->attributes['sales_at'] = Carbon::now();
    }

    public function getAllowedStatusCases(): array
    {
        $cases = collect(BookStatus::publicCases());

        $cases = match ($this->getOriginal('status')) {
            BookStatus::WORKING => $this->hasSales() ? $cases->forget(BookStatus::HIDDEN) : $cases,// нельзя перевести в статус "Скрыто" если куплена хотя бы 1 раз
            BookStatus::COMPLETE => $cases->only(BookStatus::HIDDEN->value), // Из “Завершено” можем перевести только в статус “Скрыто”.
            BookStatus::FROZEN => collect(),
            BookStatus::HIDDEN => !$this->isPublished() && $this->hadCompleted() ? collect() : $cases,//Если из статуса “Скрыто” однажды перевели книгу в статус “Завершено”, то книгу можно вернуть в статус “Скрыто” но редактирование и удаление глав будет невозможным.
            default => $cases
        };

        $cases[$this->getOriginal('status')->value] = $this->getOriginal('status');

        return $cases->toArray();
    }

    public function shouldDeferredUpdate(): bool
    {
        return $this->getOriginal('status') === BookStatus::COMPLETE
            && !$this->isFree();
    }

    public function hasSales()
    {
        return $this->sells()->exists();
    }

    public function shouldRevisionLength(): bool
    {
        return $this->isDirty('length')
            && !$this->shouldDeferredUpdate()
            && in_array($this->getOriginal('status'), [BookStatus::WORKING, BookStatus::FROZEN, BookStatus::COMPLETE]);
    }

    protected function beforeUpdate()
    {
        if (!$this->shouldRevisionLength()) {
            $this->revisionable = array_diff_key($this->revisionable, ['length']);
        }
    }

    protected function afterUpdate()
    {

    }

    public function scopeNotEmpty(Builder $builder): Builder
    {
        return $builder->whereNotNull('length')->where('length', '>', '0');
    }

    public function scopeWithLastLengthRevision(Builder $builder): Builder
    {
        return $builder->with(['revision_history' => fn($history) => $history->where('field', '=', 'length')->orderByDesc('created_at')->limit(1)]);
    }

    public function scopeWithProgress(Builder $builder, User $user): Builder
    {
        return $builder->withMax(['trackers as progress' => fn($trackers) => $trackers->user($user)->withoutTodayScope()], 'progress');
    }

    public function scopeMinPrice(Builder $builder, ?int $price): Builder
    {
        return $builder->where('price', '>=', $price);
    }

    public function scopeMaxPrice(Builder $builder, ?int $price): Builder
    {
        return $builder->where('price', '<=', $price);
    }

    public function scopeFree(Builder $builder, bool $free = true): Builder
    {
        if ($free) {
            return $builder->where('price', '=', 0);
        } else {
            return $builder->minPrice(1);
        }
    }

    public function scopeSelling(Builder $builder): Builder
    {
        return $builder
            ->free(false)
            ->whereIn('status', [BookStatus::WORKING, BookStatus::COMPLETE]);
    }

    public function scopeEbook(Builder $builder): Builder
    {
        return $builder->type(EditionsEnums::Ebook);
    }

    public function scopeAudio(Builder $builder): Builder
    {
        return $builder->type(EditionsEnums::Audio);
    }

    public function scopePhysic(Builder $builder): Builder
    {
        return $builder->type(EditionsEnums::Physic);
    }

    public function scopeComics(Builder $builder): Builder
    {
        return $builder->type(EditionsEnums::Comics);
    }

    public function scopeType(Builder $builder, EditionsEnums ...$types): Builder
    {
        return $builder->whereIn('type', $types);
    }

    public function scopeStatus(Builder $builder, BookStatus ...$status): Builder
    {
        return $builder->whereIn('status', $status);
    }

    public function nextChapterSortOrder()
    {
        return ($this->chapters()->max('sort_order') ?? 0) + 1;
    }

    public function lengthRecount()
    {
        $this->length = (int)$this->chapters()->published()->sum('length');
        $this->save();
    }

    public function changeChaptersOrder(array $ids, ?array $order = null)
    {
        Db::transaction(function () use ($ids, $order) {
            $order ??= $this->chapters()->pluck((new Chapter())->getSortOrderColumn())->toArray();
            $this->chapters()->first()->setSortableOrder($ids, $order);
            $this->chapters()->get()->each->setNeighbours();
            $this->setFreeParts();
        });
    }

    public function setFreeParts()
    {
        Db::transaction(function () {
            $builder = fn() => $this->chapters()->type(ChapterStatus::PLANNED, ChapterStatus::PUBLISHED);
            if ($this->isFree() || $this->status === BookStatus::FROZEN) {
                $builder()->update(['sales_type' => ChapterSalesType::FREE]);
            } else {
                $builder()->limit($this->free_parts)->update(['sales_type' => ChapterSalesType::FREE]);
//            $this->chapters()->offset($this->free_parts); ошибка
                $builder()->get()->skip($this->free_parts)->each->update(['sales_type' => ChapterSalesType::PAY]);
            }
            $this->chapters()->published()->get()->each->setNeighbours();
        });
    }

    public function paginateContent()
    {
        $this->chapters()->get()->each->paginateContent();
    }

    public function parseFB2(File $fb2): void
    {
        ParseFB2::dispatch($this, $fb2);
    }

    public function setParsingFailed(): void
    {
        $this->fill(['status' => BookStatus::PARSING_FAILED]);
        $this->save();
    }

    public function setHiddenStatus(): void
    {
        $this->fill(['status' => BookStatus::HIDDEN]);
        $this->save();
    }

    public function froze()
    {
        $this->fill(['status' => BookStatus::FROZEN]);
        $this->save();
    }


}
