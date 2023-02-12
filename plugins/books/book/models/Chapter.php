<?php

namespace Books\Book\Models;

use Books\Book\Classes\ChapterService;
use Books\Book\Classes\Enums\ChapterSalesType;
use Books\Book\Classes\Enums\ChapterStatus;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Jobs\JobPaginate;
use Books\Book\Jobs\JobProgress;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use Queue;
use RainLab\User\Models\User;
use System\Models\File;

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
 *
 * @property ?Chapter prev
 * @property ?Chapter next
 */
class Chapter extends Model
{
    use Sortable;
    use Validation;
    use SoftDelete;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_book_chapters';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    public string $trackerChildRelation = 'pagination';

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'title', 'edition_id', 'content', 'published_at', 'length', 'sort_order', 'status', 'sales_type', 'type',
        'next_id', 'prev_id',
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'nullable|string',
        'edition_id' => 'required|exists:books_book_editions,id',
        'content' => 'nullable|string',
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
    protected $appends = [];

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

    public function service(): ChapterService
    {
        return new ChapterService($this);
    }

    public function paginateContent($force = false)
    {
        if ($force || $this->wasChanged('content')) {
            Queue::push(JobPaginate::class, ['chapter_id' => $this->id]);
        }
    }

    public function scopeSortOrder(Builder $builder, int $value): Builder
    {
        return $builder->where($this->getSortOrderColumn(), '=', $value);
    }


    public function scopePublished(Builder $query, bool $until_now = true)
    {
        return $query
            ->where('status', ChapterStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->when($until_now, function (Builder $builder) {
                return $builder->where('published_at', '<', Carbon::now());
            });
    }

    public function getIsWillPublishedAttribute(): bool
    {
        return $this->status === ChapterStatus::PUBLISHED && $this->published_at->gt(Carbon::now());
    }

    public function lengthRecount()
    {
        $this->length = (int)$this->pagination()->sum('length') ?? 0;
        $this->save();
    }

    public function setNeighbours()
    {
        $this->setPrev();
        $this->setNext();
    }

    public function setPrev()
    {
        $this->update([
            'prev_id' => $this->edition->chapters()->sortOrder($this->{$this->getSortOrderColumn()} - 1)?->first()?->id,
        ]);

    }

    public function setNext()
    {
        $this->update([
            'next_id' => $this->edition->chapters()->sortOrder($this->{$this->getSortOrderColumn()} + 1)?->first()?->id,
        ]);
    }

    public function progress(?User $user = null)
    {
        Queue::push(JobProgress::class, ['chapter_id' => $this->id, 'user_id' => $user?->id]);
    }

}
