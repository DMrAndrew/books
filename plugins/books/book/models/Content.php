<?php namespace Books\Book\Models;

use Books\Book\Classes\Enums\ContentStatus;
use DiDom\Document;
use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Exception\UnsupportedFunctionException;
use Jfcherng\Diff\Factory\RendererFactory;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use Books\Book\Classes\Enums\ContentTypeEnum;

/**
 * Content Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 *
 * @property string $body
 * @property ContentStatus $status
 * @property ContentTypeEnum $type
 */
class Content extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_contents';

    protected $fillable = ['body', 'type', 'requested_at', 'merged_at', 'data', 'status'];
    /**
     * @var array rules for validation
     */
    public $rules = [
        'body' => 'nullable|string'
    ];


    protected $casts = [
        'type' => ContentTypeEnum::class,
        'status' => ContentStatus::class
    ];
    protected $jsonable = ['data'];

    protected $dates = [
        'requested_at',
        'merged_at'
    ];


    public $morphTo = [
        'contentable' => []
    ];

    public function scopeRegular(Builder $builder): Builder
    {
        return $builder->whereNull('type');
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

    public function scopeFilterByChapterTitle(Builder $builder, string $value)
    {

        return $builder->whereHasMorph('contentable', Chapter::class, fn($contentable) => $contentable->where('title', 'LIKE', "%{$value}%"));
    }

    public function scopeFilterByChapterId(Builder $builder, string $value)
    {

        return $builder->whereHasMorph('contentable', Chapter::class, fn($contentable) => $contentable->where('id', $value));
    }

    public function scopeFilterByBookTitle(Builder $builder, string $value)
    {

        return $builder->whereHasMorph('contentable', Chapter::class, fn($contentable) => $contentable->whereHas('edition.book', fn($book) => $book->where('title', 'LIKE', "%{$value}%")));
    }

    public function scopeFilterByBookId(Builder $builder, string $value)
    {

        return $builder->whereHasMorph('contentable', Chapter::class, fn($contentable) => $contentable->whereHas('edition.book', fn($book) => $book->where('id', $value)));
    }

    public function scopeDeferred(Builder $builder)
    {
        return $builder->type(ContentTypeEnum::DEFERRED);
    }

    public function scopeType(Builder $builder, ContentTypeEnum ...$type): Builder
    {
        return $builder->where('type', '=', array_pluck($type, 'value'));
    }

    public function scopeNotRequested(Builder $builder): Builder
    {
        return $builder->whereNull('requested_at');
    }


    public function scopeRequested(Builder $builder): Builder
    {
        return $builder->whereNotNull('requested_at');
    }

    public function scopeNotMerged(Builder $builder): Builder
    {
        return $builder->whereNull('merged_at');
    }

    public function scopeMerged(Builder $builder): Builder
    {
        return $builder->whereNotNull('merged_at');
    }

    public function scopeDeferredOpened(Builder $builder)
    {
        return $builder->deferred()->notMerged();
    }

    public function allowedMarkAsRequested(): bool
    {
        return !in_array($this->status, [ContentStatus::Pending, ContentStatus::Merged]);
    }

    public function markRequested(): void
    {
        $this->requested_at = now();
        $this->status = ContentStatus::Pending;
        $this->save();
    }


    public function markMerged(): void
    {
        $this->merged_at = now();
        $this->status = ContentStatus::Merged;
        $this->save();
    }

    public function markRejected(): void
    {
        $this->status = ContentStatus::Rejected;
        $this->save();
    }

    public function getStatusLabelAttribute(): ?string
    {
        return $this->status?->label();
    }

    protected function afterSave()
    {
        if ($this->isDirty('body') && $this->type === ContentTypeEnum::DEFERRED) {
            $this->storeDiff();
        }
    }

    public function storeDiff()
    {
        if (!($this->contentable?->content?->body)) {
            return;
        }
        $config = config('books.book::content_diff');

        $diff = DiffHelper::calculate(
            (new Document())->loadHtml($this->contentable?->content?->body)->html(),
            (new Document())->loadHtml($this->body)->html(),
            config('books.book::content_diff')['rendererName']  ?? 'Inline',
            config('books.book::content_diff')['differOptions'] ?? [], $config['rendererOptions'] ?? []);
        $this->fresh()->update(['data' => $diff]);

    }

    public function getContentDiffAttribute(): string
    {
        return $this->data ?? '-';

    }

}
