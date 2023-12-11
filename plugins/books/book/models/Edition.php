<?php

namespace Books\Book\Models;

use Books\Book\Classes\EditionService;
use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\ChapterSalesType;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Enums\ElectronicFormats;
use Books\Book\Classes\PriceTag;
use Books\Book\Classes\Services\AudioFileLengthHelper;
use Books\Book\Classes\UpdateHistory;
use Books\Book\Classes\UpdateHistoryView;
use Books\Book\Jobs\ParseFB2;
use Books\Orders\Classes\Contracts\ProductInterface;
use Carbon\Carbon;
use DateTime;
use Db;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Database\Relations\MorphMany;
use October\Rain\Database\Traits\Purgeable;
use October\Rain\Database\Traits\Revisionable;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
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
 * * @method MorphMany customers купленных или приобретенных другим способом
 * * @method MorphMany revision_history
 *
 * * @property  Book book
 * * @property  BookStatus status
 * * @property  EditionsEnums type
 * * @property  bool download_allowed
 * * @property  int price
 * * @property  bool is_deferred
 * * @property  bool is_has_customers
 * * @property  bool is_has_completed
 * * @property  Carbon last_length_update_notification_at
 * * @property  Carbon last_updated_at
 */
class Edition extends Model implements ProductInterface
{
    use Validation;
    use SoftDelete;
    use Revisionable;
    use Purgeable;
    use HasRelationships;

    const LAST_LENGTH_UPDATE_NOTIFICATION_AT_COLUMN = 'last_length_update_notification_at';

    /**
     * @var string table name
     */
    public $table = 'books_book_editions';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    protected $appends = [
        'read_percent',
    ];

    protected $revisionable = ['length', 'status', 'price'];

    protected $purgeable = ['is_deferred', 'is_has_customers', 'is_has_completed', self::LAST_LENGTH_UPDATE_NOTIFICATION_AT_COLUMN, 'last_updated_at'];

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
        'is_deferred',
        'is_has_customers',
        'is_has_completed',
        self::LAST_LENGTH_UPDATE_NOTIFICATION_AT_COLUMN,
        'last_updated_at',
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

    protected $dates = ['sales_at', 'last_notification_at'];

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

    public function pagination(): HasManyDeep
    {
        return $this->hasManyDeepFromRelationsWithConstraints(
            [$this, 'chapters'],
            [new Chapter(), 'pagination'],
        );
    }

    public function products(): MorphMany
    {
        return $this->morphMany(UserBook::class, 'ownable');
    }

    public function discount(): HasOne
    {
        return $this->hasOne(Discount::class, 'edition_id', 'id')->active();
    }

    public function service(): EditionService
    {
        return new EditionService($this);
    }

    public function markLastLengthUpdateNotificationAt(): void
    {
        $this->revision_history()->insert([
            'field' => self::LAST_LENGTH_UPDATE_NOTIFICATION_AT_COLUMN,
            'revisionable_type' => self::class,
            'revisionable_id' => $this->getKey(),
            'user_id' => $this->revisionableGetUser(),
            'created_at' => new DateTime,
            'updated_at' => new DateTime,
        ]);

    }

    public function getLastLengthUpdateNotificationAtAttribute(): ?Carbon
    {
        $this->attributes[self::LAST_LENGTH_UPDATE_NOTIFICATION_AT_COLUMN] ??= $this->revision_history()
            ->where('field', '=', self::LAST_LENGTH_UPDATE_NOTIFICATION_AT_COLUMN)
            ->latest('id')->first()?->created_at;

        return $this->attributes[self::LAST_LENGTH_UPDATE_NOTIFICATION_AT_COLUMN];
    }

    public function allowedDownload(ElectronicFormats $format = ElectronicFormats::FB2): bool
    {
        return $this->download_allowed
            && $this->hasRelation($format->value)
            && $this->{$format->value};
    }

    public function getLastUpdatedAtAttribute(): ?Carbon
    {
        $this->attributes['last_updated_at'] ??= $this->revision_history()
            ->where('field', '=', 'length')
            ->orderByDesc('created_at')
            ->first()?->created_at;

        return $this->attributes['last_updated_at'];
    }

    public function collectUpdateHistory(): UpdateHistory
    {
        return new UpdateHistory(
            $this->revision_history()->where('field', '=', 'length')->get(),
            $this->type
        );
    }

    public function getUpdateHistoryViewAttribute(): UpdateHistoryView
    {
        return $this->collectUpdateHistory()->toView();
    }

    public function priceTag(): PriceTag
    {
        return new PriceTag($this, $this->discount);
    }

    public function scopeWithReadLength(Builder $builder, User $user): Builder
    {
        return $builder->withSum(['trackers as read_length' => fn ($trackers) => $trackers->withoutTodayScope()->user($user)->latest('updated_at')->limit(1)], 'length');
    }

    public function getReadPercentAttribute(): int
    {
        return match ($this->type) {
            EditionsEnums::Ebook => min(100, (int) ceil((($this->read_length ?? 0) * 100) / $this->length)),
            EditionsEnums::Audio => 0, // todo
        };
    }

    public function getTitleAttribute(): string
    {
        return match ($this->type) {
            EditionsEnums::Ebook => $this->book->title,
            default => $this->book->title . ' ('. EditionsEnums::Audio->label().') ',
        };
    }

    public function scopeWithActiveDiscountExist(Builder $builder): Builder
    {
        return $builder->withExists('discount');
    }

    public function scopeActiveDiscountExist(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->has('discount');
    }

    public function scopeWithFiles(Builder $builder): Builder
    {
        return $builder->with(ElectronicFormats::FB2->value);
    }

    public function getAllowedForDiscountAttribute(): bool
    {
        return in_array($this->getOriginal('status'), [BookStatus::COMPLETE, BookStatus::WORKING]) && ! $this->isFree();
    }

    public function scopeAllowedForDiscount(Builder $builder): Builder
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

    public function scopeWithSellsCount(Builder $builder): Builder
    {
        return $builder->withCount('sells');
    }

    public function isFree(): bool
    {
        return ! (bool) (int) $this->getOriginal('price');
    }

    public function hasRevisionStatus(BookStatus ...$status)
    {
        return $this->revision_history()
            ->where('field', 'status')
            ->whereIn('old_value', array_pluck($status, 'value'))
            ->exists();
    }

    public function isPublished(): bool
    {
        return (bool) $this->getOriginal('sales_at');
    }

    public function setPublishAt(): void
    {
        $this->attributes['sales_at'] = Carbon::now();
    }

    public function sellsSettingsEditAllowed(): bool
    {
        return $this->getOriginal('status') !== BookStatus::FROZEN;
    }

    /**
     * Разрешено редактировать книгу
     */
    public function editAllowed(): bool
    {
        return ! $this->is_deferred;
    }

    /**
     * Функция определяет разрешённые статусы для издания
     */
    public function getAllowedStatusCases(): array
    {
        $cases = collect(BookStatus::publicCases());

        if ($this->exists) {
            $cases = match ($this->getOriginal('status')) {
                BookStatus::WORKING => $this->is_has_customers ? $cases->forget(BookStatus::HIDDEN) : $cases,
                // нельзя перевести в статус "Скрыто" если куплена хотя бы 1 раз
                BookStatus::COMPLETE => $cases->only(BookStatus::HIDDEN->value),
                // Из “Завершено” можем перевести только в статус “Скрыто”.
                BookStatus::HIDDEN => $this->isPublished() && $this->is_has_completed && $this->is_has_customers ? $cases->only(BookStatus::COMPLETE->value) : $cases,
                //Если из статуса “Скрыто” однажды перевели книгу в статус “Завершено”,
                // то книгу можно вернуть в статус “Скрыто”, но редактирование и удаление глав будет невозможным если есть продажи.
                default => collect()
            };

            $cases[$this->getOriginal('status')->value] = $this->getOriginal('status');
        }

        return $cases->toArray();
    }

    /**
     * Функция определяет подлежит ли книга отложенному редактированию
     */
    public function shouldDeferredUpdate(): bool
    {
        return
            // в статусе
            in_array(
            $this->getOriginal('status'), [BookStatus::HIDDEN, BookStatus::COMPLETE])
            // и есть продажи
            && $this->is_has_customers;
    }

    public function hasCompleted(): bool
    {
        return $this->hasRevisionStatus(BookStatus::COMPLETE);
    }

    public function hasCustomers(): bool
    {
        return $this->customers()->exists();
    }

    public function getIsDeferredAttribute(): bool
    {
        $this->attributes['is_deferred'] ??= $this->shouldDeferredUpdate();

        return $this->attributes['is_deferred'];
    }

    public function getIsHasCustomersAttribute()
    {
        $this->attributes['is_has_customers'] ??= $this->hasCustomers();

        return $this->attributes['is_has_customers'];
    }

    public function getIsHasCompletedAttribute()
    {
        $this->attributes['is_has_completed'] ??= $this->hasCompleted();

        return $this->attributes['is_has_completed'];
    }

    public function hasSales(): bool
    {
        return $this->sells()->exists();
    }

    public function shouldRevisionLength(): bool
    {
        return $this->isDirty('length')
            && ! $this->is_deferred
            && in_array($this->getOriginal('status'), [BookStatus::WORKING, BookStatus::FROZEN, BookStatus::COMPLETE]);
    }

    protected function beforeUpdate(): void
    {
        if (! $this->shouldRevisionLength()) {
            $this->revisionable = array_diff_key($this->revisionable, ['length']);
        }
        $this->purgeAttributes();
    }

    public function scopeNotEmpty(Builder $builder): Builder
    {
        return $builder->whereNotNull('length')->where('length', '>', '0');
    }

    public function scopeWithLastLengthRevision(Builder $builder): Builder
    {
        return $builder->with(['revision_history' => fn ($history) => $history->where('field', '=', 'length')->orderByDesc('created_at')->limit(1)]);
    }

    public function scopeWithProgress(Builder $builder, User $user): Builder
    {
        return $builder->withMax(['trackers as progress' => fn ($trackers) => $trackers->user($user)->withoutTodayScope()], 'progress');
    }

    public function scopeMinPrice(Builder $builder, ?int $price): Builder
    {
        return $builder->where($this->getQualifiedPriceColumn(), '>=', $price);
    }

    public function scopeMaxPrice(Builder $builder, ?int $price): Builder
    {
        return $builder->where($this->getQualifiedPriceColumn(), '<=', $price);
    }

    public function scopeFree(Builder $builder, bool $free = true): Builder
    {
        return $builder->when($free,
            fn ($q) => $q->where($this->getQualifiedPriceColumn(), '=', 0),
            fn ($q) => $q->minPrice(1)
        );
    }

    public function scopeSelling(Builder $builder): Builder
    {
        return $builder
            ->status(BookStatus::WORKING, BookStatus::COMPLETE)
            ->free(false);
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
        return $builder->whereIn($this->getQualifiedTypeColumn(), $types);
    }

    public function scopeStatus(Builder $builder, BookStatus ...$status): Builder
    {
        return $builder->whereIn($this->getQualifiedStatusColumn(), $status);
    }

    public function nextChapterSortOrder()
    {
        return ($this->chapters()->max('sort_order') ?? 0) + 1;
    }

    public function lengthRecount()
    {
        $this->fill(['length' => (int) $this->chapters()->public()->sum('length')]);
        $this->save();
        $this->setFreeParts();
    }

    public function changeChaptersOrder(array $ids, array $order = null)
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
        $builder = fn () => $this->chapters()->public(withPlanned: true);
        if ($this->isFree() || $this->status === BookStatus::FROZEN) {
            $builder()->update(['sales_type' => ChapterSalesType::FREE]);
        } else {
            $builder()->limit($this->free_parts)->update(['sales_type' => ChapterSalesType::FREE]);
            //            $this->chapters()->offset($this->free_parts); ошибка
            $builder()->get()->skip($this->free_parts)->each->update(['sales_type' => ChapterSalesType::PAY]);
        }
        $this->chapters()->public()->get()->each->setNeighbours();
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

    public function froze(): void
    {
        $this->fill(['status' => BookStatus::FROZEN]);
        $this->save();
    }

    public static function lookUpFroze()
    {
        $date = today()->copy()->subDays(config('books.book.free_working_days_before_frozen', 30));

        return static::query()
            ->status(BookStatus::WORKING)
            ->whereHas('revision_history', fn ($revision_history) => $revision_history
                ->whereDate('created_at', '<=', $date)
                ->where(['field' => 'status', 'new_value' => BookStatus::WORKING->value]))
            ->get()
            ->filter(function (Edition $edition) use ($date) {
                return $edition->collectUpdateHistory()->getChunks()->last()?->date->lessThan($date);
            });
    }

    public function getQualifiedStatusColumn(): string
    {
        return $this->qualifyColumn('status');
    }

    public function getQualifiedTypeColumn(): string
    {
        return $this->qualifyColumn('type');
    }

    public function getQualifiedPriceColumn(): string
    {
        return $this->qualifyColumn('price');
    }

    public function getAudioLengthAttribute(): ?string
    {
        if ($this->type != EditionsEnums::Audio) {
            return null;
        }

        return AudioFileLengthHelper::formatSecondsToHumanReadableTime($this->length);
    }

    public function getAudioLengthShortAttribute(): ?string
    {
        if ($this->type != EditionsEnums::Audio) {
            return null;
        }

        return AudioFileLengthHelper::getAudioLengthHumanReadableShort($this->length);
    }
}
