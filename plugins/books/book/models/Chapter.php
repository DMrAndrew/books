<?php

namespace Books\Book\Models;

use Books\Book\Classes\ChapterService;
use Books\Book\Classes\Contracts\iChapterService;
use Books\Book\Classes\DeferredChapterService;
use Books\Book\Classes\Enums\ChapterSalesType;
use Books\Book\Classes\Enums\ChapterStatus;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Reader;
use Books\Book\Classes\ScopeToday;
use Books\Book\Jobs\Paginate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Database\Relations\MorphOne;
use October\Rain\Database\Traits\Purgeable;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use System\Models\File;
use ValidationException;

/**
 * Chapter Model
 *
 * @method AttachOne file
 * @method HasMany pagination
 * @method BelongsTo edition
 *
 * @property Edition edition
 *
 * * @method AttachOne audio
 * * @method AttachOne picture
 * @method HasOne next
 * @method HasOne prev
 * @method MorphMany deferred
 *
 * @property ?Chapter prev
 * @property ?Chapter next
 *
 * @method MorphOne content
 *
 * @property  Content content
 * @property  EditionsEnums type
 * @property  ChapterStatus status
 * @property  ChapterSalesType sales_type
 * @property  int sort_order
 */
class Chapter extends Model
{
    use Sortable;
    use Validation;
    use SoftDelete;
    use Purgeable;
    use HasRelationships;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_book_chapters';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    protected $purgeable = ['new_content', 'deferred_content'];

    public string $trackerChildRelation = 'pagination';

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'title', 'edition_id', 'published_at', 'new_content', 'deferred_content', 'length', 'sort_order', 'status', 'sales_type', 'type',
        'next_id', 'prev_id',
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'nullable|string',
        'edition_id' => 'required|exists:books_book_editions,id',
        'published_at' => 'nullable|date',
        'length' => 'nullable|integer',
        'sort_order' => 'sometimes|filled|integer',
        'next_id' => 'nullable|integer|exists:books_book_chapters,id',
        'prev_id' => 'nullable|integer|exists:books_book_chapters,id',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'type' => EditionsEnums::class,
        'status' => ChapterStatus::class,
        'sales_type' => ChapterSalesType::class,
        'audio' => ['nullable', 'file', 'mimes:mp3,mp4'],
        'picture' => ['nullable', 'file', 'jpeg,jpg,png'],
    ];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

    /**
     * @var array appends attributes to the API representation of the model (ex. toArray())
     */
    protected $appends = [

    ];

    /**
     * @var array hidden attributes removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'published_at',
    ];

    public $hasMany = [
        'pagination' => [Pagination::class, 'key' => 'chapter_id', 'otherKey' => 'id'],
    ];

    public $belongsTo = [
        'edition' => [Edition::class, 'key' => 'edition_id', 'otherKey' => 'id'],
        'next' => [Chapter::class, 'key' => 'next_id', 'otherKey' => 'id'],
        'prev' => [Chapter::class, 'key' => 'prev_id', 'otherKey' => 'id'],
    ];

    public $belongsToMany = [];

    public $morphTo = [];

    public $morphOne = [];

    public $morphMany = [];

    public $attachOne = [
        'audio' => File::class,
        'picture' => File::class,
    ];

    public $attachMany = [];

    public function reader(): Reader
    {
        return new Reader($this->edition->book, $this);
    }

    public function service(): iChapterService
    {
        return $this->edition?->is_deferred ? $this->deferredService(...func_get_args()) : $this->chapterService(...func_get_args());
    }

    public function chapterService(): iChapterService
    {
        return new ChapterService($this, ...func_get_args());
    }

    public function deferredService(): iChapterService
    {
        return new DeferredChapterService($this, ...func_get_args());
    }

    public function getTitleAttribute()
    {
        return $this->attributes['title'] ?? sprintf('№%s', $this->{$this->getSortOrderColumn()});
    }

    public function isFree(): bool
    {
        return $this->sales_type === ChapterSalesType::FREE;
    }

    public function paginationTrackers()
    {
        return $this->hasManyDeepFromRelationsWithConstraints(
            [$this, 'pagination'],
            [new Pagination(), 'trackers']
        )->withoutGlobalScope(new ScopeToday());
    }

    public function paginateContent()
    {
        Paginate::dispatch($this);
    }

    public function scopeSortOrder(Builder $builder, int $value): Builder
    {
        return $builder->where($this->getQualifiedSortOrderColumn(), '=', $value);
    }

    public function scopeMinSortOrder(Builder $builder, int $value): Builder
    {
        return $builder->where($this->getQualifiedSortOrderColumn(), '>', $value);
    }

    public function scopeMaxSortOrder(Builder $builder, int $value): Builder
    {
        return $builder->where($this->getQualifiedSortOrderColumn(), '<', $value);
    }

    public function scopePlanned(Builder $builder): Builder
    {
        return $builder->where($this->getQualifiedStatusColumn(), ChapterStatus::PLANNED);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where($this->getQualifiedStatusColumn(), ChapterStatus::PUBLISHED);
    }

    public function scopePublic(Builder $builder, bool $withPlanned = false)
    {
        return $builder
            ->where($this->qualifyColumn('length'), '>', 0)
            ->when($withPlanned, fn ($q) => $q->where(fn ($where) => $where->published()->orWhere(fn ($or) => $or->planned())), fn ($q) => $q->published())
            ->whereDoesntHave('deferred', fn ($deferred) => $deferred->deferred()->deferredCreate());
    }

    public function scopeType(Builder $builder, ChapterStatus ...$status): Builder
    {
        return $builder->whereIn($this->getQualifiedStatusColumn(), array_pluck($status, 'value'));
    }

    public function lengthRecount(): void
    {
        $this->fill(['length' => (int) $this->pagination()->sum('length') ?? 0]);
        $this->save();
        $this->edition()->first()->lengthRecount();
    }

    public function setNeighbours(): void
    {
        $builder = fn () => $this->edition()->first()->chapters()->public();
        $sort_order = $this->{$this->getSortOrderColumn()};
        $this->update([
            'prev_id' => $builder()->maxSortOrder($sort_order)->latest($this->getSortOrderColumn())->first()?->id,
            'next_id' => $builder()->minSortOrder($sort_order)->first()?->id,
        ]);
    }

    protected function afterSave(): void
    {
        if ($this->isDirty(['status'])) {
            $fresh = $this->fresh();
            $this->edition()->first()->setFreeParts();
            if ($fresh->status === ChapterStatus::PUBLISHED) {
                $fresh->lengthRecount();
            }
        }
    }

    protected function afterCreate()
    {
        $this->edition()->first()->setFreeParts();
    }

    public function afterDelete()
    {
        $this->prev?->setNeighbours();
        $this->next?->setNeighbours();
        $this->edition->setFreeParts();
        $this->lengthRecount();
    }

    public function getContent()
    {
        return $this->deferred()->deferredCreateOrUpdate()->first() ?? $this->content;
    }

    public function getQualifiedStatusColumn(): string
    {
        return $this->qualifyColumn('status');
    }
}
