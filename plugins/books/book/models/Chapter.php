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
use Books\Book\Jobs\Reading;
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
use RainLab\User\Models\User;
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


    public function reader()
    {
        return new Reader($this->edition->book, $this);
    }

    /**
     * test
     *
     * @throws ValidationException
     */
    public function groupedTrackers(string $groupBy = 'user_id')
    {
        if (!in_array($groupBy, ['user_id', 'ip'])) {
            throw new ValidationException(['column' => 'user_id or ip only']);
        }
        $tracker = (new Tracker());
        $column = $tracker->qualifyColumn($groupBy);
        $raw = sprintf('max( DATE(%s)) as date ,%s, count(*) as total_trackers, sum(%s) as total_time, sum(%s) as total_length, sum(%s) as total_progress',
            $tracker->qualifyColumn('created_at'),
            $tracker->qualifyColumn('trackable_id'),
            $tracker->qualifyColumn('time'),
            $tracker->qualifyColumn('length'),
            $tracker->qualifyColumn('progress'),
        );

        return $this->paginationTrackers()
            ->withoutTodayScope()
            ->whereNotNull($column)
            ->when($groupBy === 'ip', fn($q) => $q->whereNull('user_id'))
            ->selectRaw($raw)
            ->groupBy($tracker->qualifyColumn('trackable_id'),$column);
    }



    public function service(): iChapterService
    {
        return $this->edition?->is_deferred ? $this->deferredService() : new ChapterService($this);
    }

    public function deferredService(): iChapterService
    {
        return new DeferredChapterService($this);
    }

    public function getTitleAttribute()
    {
        return $this->attributes['title'] ?? false ?: ($this->exists ? '№' . $this->{$this->getSortOrderColumn()} : '');
    }

    public function isFree(): bool
    {
        return $this->sales_type === ChapterSalesType::FREE;
    }

    public function groupTrackers(string $field)
    {
        if (!in_array($field, ['user_id', 'ip'])) {
            throw new \ValidationException(['column' => 'user_id or ip only']);
        }
        $tracker = (new Tracker());
        $column = $tracker->qualifyColumn($field);
        $raw = sprintf('%s, count(%s) as trackers_count, sum(%s) as time, sum(%s) as length', $column, $tracker->getQualifiedKeyName(),$tracker->qualifyColumn('time'),$tracker->qualifyColumn('length'));

        $query = match ($field){
            'ip' => fn($q) => $q->whereNull('user_id'),
            'user_id' => fn($q) => $q,
        };

        return $query($this->paginationTrackers()->whereNotNull($column)->completed()->selectRaw($raw)->groupBy($column));
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
        return $builder->where($this->getSortOrderColumn(), '=', $value);
    }

    public function scopeMinSortOrder(Builder $builder, int $value): Builder
    {
        return $builder->where($this->getSortOrderColumn(), '>', $value);
    }

    public function scopeMaxSortOrder(Builder $builder, int $value): Builder
    {
        return $builder->where($this->getSortOrderColumn(), '<', $value);
    }

    public function scopePlanned(Builder $builder): Builder
    {
        return $builder->where('status', ChapterStatus::PLANNED);
    }

    public function scopePublished(Builder $query)
    {
        return $query->where('status', ChapterStatus::PUBLISHED);
    }

    public function scopePublic(Builder $builder, bool $withPlanned = false)
    {
        return $builder
            ->where('length', '>', 0)
            ->when($withPlanned, fn($q) => $q->where(fn($where) => $where->published()->orWhere(fn($or) => $or->planned())), fn($q) => $q->published())
            ->whereDoesntHave('deferred', fn($deferred) => $deferred->deferred()->deferredCreate());
    }

    public function scopeWithDeferredState(Builder $builder)
    {
        return $builder
            ->withDeferredUpdateExists()
            ->withDeferredUpdateNotRequestedExists()
            ->withDeferredUpdateRequestedExists()
            ->withDeferredDeleteExists();
    }

    public function scopeType(Builder $builder, ChapterStatus ...$status): Builder
    {
        return $builder->whereIn('status', array_pluck($status, 'value'));
    }

    public function lengthRecount()
    {
        $this->length = (int)$this->pagination()->sum('length') ?? 0;
        $this->save();
        $this->edition()->first()->lengthRecount();
    }

    public function setNeighbours()
    {
        $builder = fn() => $this->edition()->first()->chapters()->public();
        $sort_order = $this->{$this->getSortOrderColumn()};
        $this->update([
            'prev_id' => $builder()->maxSortOrder($sort_order)->latest($this->getSortOrderColumn())->first()?->id,
            'next_id' => $builder()->minSortOrder($sort_order)->first()?->id,
        ]);
    }

    protected function afterSave()
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

    public function progress(?User $user = null)
    {
        Reading::dispatch($this, $user);
    }

    public function getContent()
    {
        return $this->deferred()->deferredCreateOrUpdate()->first() ?? $this->content;
    }


}
