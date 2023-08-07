<?php namespace Books\Book\Models;

use Books\Book\Classes\BookUtilities;
use Books\Book\Classes\ContentInfoHelper;
use Books\Book\Classes\ContentService;
use Books\Book\Classes\Enums\ContentStatus;
use Carbon\Carbon;
use Closure;
use Exception;
use Jfcherng\Diff\DiffHelper;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Purgeable;
use October\Rain\Database\Traits\Revisionable;
use October\Rain\Database\Traits\Validation;
use Books\Book\Classes\Enums\ContentTypeEnum;
use RainLab\User\Facades\Auth;
use System\Models\Revision;

/**
 * Content Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 *
 * @property string $body
 * @property ContentStatus $status
 * @property ContentTypeEnum $type
 * @property $contentable
 * @property int $length
 */
class Content extends Model
{
    use Validation;
    use Revisionable;
    use Purgeable;

    /**
     * @var string table name
     */
    public $table = 'books_book_contents';

    protected $fillable = ['body', 'type', 'requested_at', 'merged_at', 'data', 'status', 'saved_from_editor'];
    protected $revisionable = ['status', 'requested_at', 'merged_at'];

    protected $purgeable = ['length'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'body' => 'nullable|string'
    ];


    protected $casts = [
        'type' => ContentTypeEnum::class,
        'status' => ContentStatus::class,
    ];
    protected $jsonable = ['data'];

    protected $dates = [
        'requested_at',
        'merged_at'
    ];


    public $morphTo = [
        'contentable' => []
    ];

    public $morphMany = [
        'revision_history' => [Revision::class, 'name' => 'revisionable']
    ];

    public function infoHelper(): ContentInfoHelper
    {
        return new ContentInfoHelper($this);
    }

    public function getBookInfoAttribute(): string
    {
        if ($this->contentable instanceof Chapter) {
            $book = $this->contentable->edition->book;
            return sprintf('%s (ID:%s)', $book->title, $book->id);
        }
        return '';
    }

    public function getChapterInfoAttribute(): string
    {
        if ($this->contentable instanceof Chapter) {
            return sprintf('%s (ID:%s)', strip_tags($this->contentable->title), $this->contentable->id);
        }
        return '';
    }

    public function service(): ContentService
    {
        return new ContentService($this, ...func_get_args());
    }

    public function scopeRegular(Builder $builder): Builder
    {
        return $builder->whereNull('type');
    }

    public function scopeNotRegular(Builder $builder): Builder
    {
        return $builder->whereNotNull('type');
    }

    public function scopeOnDeleteType(Builder $builder): Builder
    {
        return $builder->type(ContentTypeEnum::DEFERRED_DELETE);
    }

    public function scopeFilterByChapterTitle(Builder $builder, string $value)
    {
        return $builder->filterChapter(fn($q) => $q->where('title', 'LIKE', "%{$value}%"));
    }

    public function scopeFilterByChapterId(Builder $builder, string $value)
    {
        return $builder->filterChapter(fn($q) => $q->where('id', $value));
    }

    public function scopeFilterByBookTitle(Builder $builder, string $value)
    {
        return $builder->filterChapter(fn($q) => $q->whereHas('edition.book', fn($book) => $book->where('title', 'LIKE', "%{$value}%")));
    }

    public function scopeFilterByBookId(Builder $builder, string $value)
    {
        return $builder->filterChapter(fn($q) => $q->whereHas('edition.book', fn($book) => $book->where('id', $value)));
    }

    public function scopeFilterChapter(Builder $builder, Closure $closure): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHasMorph('contentable', Chapter::class, $closure);
    }

    public function scopeType(Builder $builder, ContentTypeEnum ...$type): Builder
    {
        return $builder->whereIn('type', array_pluck($type, 'value'));
    }

    public function scopeTypeNot(Builder $builder, ContentTypeEnum ...$type): Builder
    {
        return $builder->whereNotIn('type', array_pluck($type, 'value'));
    }

    public function scopeStatus(Builder $builder, ContentStatus ...$status): Builder
    {
        return $builder->whereIn('status', array_pluck($status, 'value'));
    }

    public function scopeStatusNotOrNull(Builder $builder, ContentStatus ...$status): Builder
    {
        return $builder->where(fn($q) => $q->statusNot(...$status)->orWhereNull('status'));
    }

    public function scopeStatusNot(Builder $builder, ContentStatus ...$status): Builder
    {
        return $builder->where(fn($q) => $q->whereNotIn('status', array_pluck($status, 'value')));
    }


    public function scopeNotRejected(Builder $builder): Builder
    {
        return $builder->statusNotOrNull(ContentStatus::Rejected);
    }


    public function scopeNotCanceled(Builder $builder): Builder
    {
        return $builder->statusNotOrNull(ContentStatus::Rejected);
    }

    public function scopeNotRequested(Builder $builder): Builder
    {
        return $builder->statusNotOrNull(ContentStatus::Pending);
    }

    public function scopeRequested(Builder $builder): Builder
    {
        return $builder->status(ContentStatus::Pending);
    }


    public function scopeNotMerged(Builder $builder): Builder
    {
        return $builder->statusNotOrNull(ContentStatus::Merged);
    }

    public function scopeMerged(Builder $builder): Builder
    {
        return $builder->status(ContentStatus::Merged);
    }

    public function scopeDeferred(Builder $builder)
    {
        return $builder->statusNotOrNull(ContentStatus::Merged)->notRegular();
    }

    public function scopeDeferredCreateOrUpdate(Builder $builder): Builder
    {
        return $builder->type(ContentTypeEnum::DEFERRED_CREATE, ContentTypeEnum::DEFERRED_UPDATE);
    }

    public function scopeDeferredDelete(Builder $builder): Builder
    {
        return $builder->type(ContentTypeEnum::DEFERRED_DELETE);
    }

    public function scopeDeferredUpdate(Builder $builder): Builder
    {
        return $builder->type(ContentTypeEnum::DEFERRED_UPDATE);
    }

    public function scopeDeferredCreate(Builder $builder): Builder
    {
        return $builder->type(ContentTypeEnum::DEFERRED_CREATE);
    }

    public function getDeferredCommentsAttribute()
    {
        return collect($this->data['comments'] ?? [])
            ->map(fn($comment) => [
                'created_at' => ($comment['created_at'] ?? false) ? Carbon::parse($comment['created_at'])->format('H:i d.m.y') : '',
                'user' => $comment['user']['email'] ?? '',
                'comment' => $comment['comment'] ?? '',
            ])
            ->map(fn($i) => implode(PHP_EOL, $i))
            ->reverse()
            ->join(PHP_EOL . PHP_EOL);
    }

    public function addComment(?string $comment = null): void
    {
        if ($comment) {
            $data = $this->data;
            $data['comments'][] = [
                'user' => (Auth::getUser() ?? \Backend\Controllers\Auth::getUser()),
                'comment' => $comment,
                'created_at' => now()
            ];
            $this->data = $data;
        }
    }


    public function allowedMarkAs(ContentStatus $status): bool
    {
        $original_status = $this->getOriginal('status');
        return match ($status) {
            ContentStatus::Rejected, ContentStatus::Merged => !is_null($original_status) && ($original_status !== ContentStatus::Cancelled),
            ContentStatus::Cancelled => $original_status === ContentStatus::Pending,
            ContentStatus::Pending => !in_array($original_status, [ContentStatus::Merged, ContentStatus::Pending]),
            default => false
        };
    }

    public function markRequested(?string $comment = null): bool
    {
        $this->fill([
            'requested_at' => now(),
            'status' => ContentStatus::Pending
        ]);
        $this->addComment($comment);
        return $this->save();
    }


    public function markMerged(?string $comment = null): bool
    {
        $this->fill([
            'merged_at' => now(),
            'status' => ContentStatus::Merged
        ]);
        $this->addComment($comment);
        return $this->save();
    }

    /**
     * @throws Exception
     */
    public function markCanceled(): bool
    {
        $this->fill([
            'status' => ContentStatus::Cancelled
        ]);
        return $this->save();
    }

    public function markRejected(?string $comment = null): bool
    {
        $this->fill([
            'merged_at' => now(),
            'status' => ContentStatus::Rejected
        ]);
        $comment && $this->addComment($comment);
        return $this->save();
    }

    public function getStatusLabelAttribute(): ?string
    {
        return $this->status?->label();
    }

    public function getTypeLabelAttribute(): ?string
    {
        return $this->type?->label();
    }

    protected function afterSave()
    {
        if (in_array($this->type, [ContentTypeEnum::DEFERRED_CREATE, ContentTypeEnum::DEFERRED_UPDATE]) && $this->isDirty('body')) {
            $this->storeDiff();
        }
    }

    public function storeDiff(): void
    {
        if (!($this->contentable?->content)) {
            return;
        }

        $diff = DiffHelper::calculate(
            BookUtilities::prepareForDiff($this->contentable->content->body ?? ''),
            BookUtilities::prepareForDiff($this->body ?? ''),
            ...(config('books.book::content_diff') ?? []));

        $this->fresh()->update(['data' => array_replace($this->data ?? [], ['diff' => $diff])]);
    }

    public function getContentDiffAttribute(): string
    {
        return $this->data['diff'] ?? '';
    }

    public function getLengthAttribute()
    {
        $this->attributes['length'] ??= BookUtilities::countContentLength(BookUtilities::stringToDiDom($this->body)->text());
        return $this->attributes['length'];
    }


}
