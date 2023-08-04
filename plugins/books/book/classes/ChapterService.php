<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\ChapterSalesType;
use Books\Book\Classes\Enums\ChapterStatus;
use Books\Book\Classes\Enums\ContentStatus;
use Books\Book\Classes\Enums\ContentTypeEnum;
use Books\Book\Classes\Exceptions\UnknownFormatException;
use Books\Book\Models\Chapter;
use Books\Book\Models\Content;
use Books\Book\Models\Edition;
use Books\Book\Models\Pagination;
use Carbon\Carbon;
use Closure;
use Db;
use Event;
use Exception;
use Illuminate\Support\Collection;
use ValidationException;

class ChapterService
{
    public function __construct(protected Chapter $chapter, protected ?Edition $edition = null)
    {
        if (!$this->isNew() && !$this->edition?->exists) {
            $this->edition = $this->chapter->edition;
        }
    }

    public function isNew(): bool
    {
        return !$this->chapter->exists;
    }

    /**
     * @param Edition $edition
     * @return ChapterService
     */
    public function setEdition(Edition $edition): static
    {
        $this->edition = $edition;

        return $this;
    }

    /**
     * @throws UnknownFormatException
     * @throws Exception
     */
    public function from(mixed $payload): ?Chapter
    {
        if ($payload instanceof \Tizis\FB2\Model\Chapter) {
            $collection = BookUtilities::parseStringToParagraphCollection($payload->getContent());
            if ((int)$collection->sum('length')) {
                $data = [
                    'title' => $payload->getTitle(),
                    'content' => $collection->pluck('html')->join(''),
                    'status' => ChapterStatus::PUBLISHED,
                ];
            } else {
                return null;
            }
        } else {
            $data = $payload;
        }


        if (is_array($data) || $data instanceof Collection) {
            $data = $this->dataPrepare(is_array($data) ? $data : $data->toArray());
            return $this->isNew() ? $this->create($data) : $this->update($data);
        }
        throw new UnknownFormatException();
    }

    /**
     * @throws Exception
     */
    protected function create(array $data): Chapter
    {
        if (!$this->edition->id) {
            throw new Exception('Edition required.');
        }
        $this->chapter->fill($data);
        $this->chapter->sort_order ??= $this->edition->nextChapterSortOrder();
        $this->chapter->sales_type ??= ChapterSalesType::PAY;
        $this->chapter['edition_id'] = $this->edition->id;
        $this->chapter->save();

        Event::fire('books.chapter.created', [$this->chapter]);

        return $this->chapter;
    }

    protected function update(array $data): Chapter
    {
        return Db::transaction(function () use ($data) {
            $this->chapter->fill($data);

            if (!$this->chapter->isDirty('status')) {
                $this->chapter->published_at = $this->chapter->getOriginal('published_at');
            }

            $this->chapter->save();
            Event::fire('books.chapter.updated', [$this->chapter]);

            return $this->chapter;
        });
    }

    public function mergeDeferred(): Chapter|bool
    {
        if ($content = $this->chapter->deferredContentOpened) {
            return $this->update(['new_content' => $content->body]);
        }
        return false;
    }

    public function initUpdateBody(string $content): bool|int
    {
        if (!$this->chapter->content->saved_from_editor) {
            return Content::query()->where('id', '=', $this->chapter->content->id)->update([
                'body' => $content,
                'saved_from_editor' => 1
            ]);
        }
        return false;
    }

    /**
     * @throws ValidationException
     */
    public function delete()
    {
        if (!$this->chapter->edition->editAllowed()) {
            if ($this->edition->shouldDeferredUpdate()) {
                $content = $this->chapter->deletedContent()->firstOrCreate(['type' => ContentTypeEnum::DEFERRED_DELETE, 'status' => ContentStatus::Pending]);
                return $content->service()->markRequested();
            } else {
                throw new ValidationException(['chapter' => 'В данный момент Вы не можете удалять главы книг.']);
            }
        }
        return $this->actionDelete();
    }

    /**
     * :(
     *
     * @return bool
     */
    public function actionDelete(): bool
    {
        return $this->chapter->delete();
    }

    public function markCanceledDeferredUpdate()
    {
        return $this->chapter->deferredContentOpened?->service()->markCanceled();
    }

    public function markCanceledDeletedContent()
    {
        return $this->chapter->deletedContent?->service()->markCanceled();
    }

    /**
     * @throws ValidationException
     */
    public function dataPrepare(array|Collection $data): array
    {
        $data = collect($data);
        if (!$this->edition->editAllowed()) {
            if ($this->edition->shouldDeferredUpdate()) {
                $data = $data->only(['content']);
            } else {
                throw new ValidationException(['edition' => 'Для этой книги запрещено редактирование глав.']);
            }
        }


        if ($data->has('status')) {
            $data['status'] = $data['status'] instanceof ChapterStatus ? $data['status'] : (ChapterStatus::tryFrom($data->get('status')) ?? ChapterStatus::DRAFT);

            if ($data->get('status') instanceof ChapterStatus) {
                switch ($data->get('status')) {
                    case ChapterStatus::PUBLISHED:

                        $data['published_at'] = Carbon::now();
                        break;

                    case ChapterStatus::PLANNED:
                        if (!($data->get('published_at') instanceof Carbon)) {
                            throw new ValidationException(['published_at' => 'Не верный формат даты публикации.']);
                        }
                        $data['published_at'] = $data['published_at']->copy()->setMinutes(0)->setSeconds(0);
                        break;

                    default:

                        $data['published_at'] = null;
                }
            } else {
                $data->forget('status');
            }
        }

        if ($data->has('content')) {
            $key = $this->edition?->shouldDeferredUpdate() ? 'deferred_content' : 'new_content';
            $data[$key] = $data['content'];
            $data->forget('content');
        }

        return $data->toArray();
    }

    public function getPaginationLinks(int $page = 1)
    {
        if (!$this->isNew()) {
            $pagination = $this->chapter->pagination;
            $links = $pagination->map(function ($item) use ($pagination, $page) {
                if (in_array($item->page, [
                    $page,
                    $page + 1,
                    $page - 1,
                    $pagination->first()->page,
                    $pagination->last()->page,
                ])) {
                    return $item;
                }

                return null;
            });

            return $links->filter(function ($value, $key) use ($links) {
                return $value || ((bool)$links[$key + 1] ?? false);
            })->values();
        }

        return null;
    }

    public function paginate(): void
    {
        $chunks = $this->chunkContent();
        $pages = $chunks->map(function ($chunk, $index) {
            return new Pagination(
                [
                    'page' => $index + 1,
                    'new_content' => $chunk->pluck('html')->join(''),
                    'length' => $chunk->sum('length'),
                ]
            );
        });
        $pagination = $pages->map(function ($paginator) {
            $page = $this->chapter->pagination()->firstOrCreate(['page' => $paginator->page], ['length' => $paginator->length]);
            $page->fill($paginator->toArray());
            $page->save();

            return $page;
        });
        $this->chapter->pagination()->whereNotIn('id', $pagination->pluck('id'))->delete();
        $this->chapter->pagination()->get()->each->setNeighbours();
        $this->chapter->lengthRecount();
        Event::fire('books.chapter.paginated');
    }

    public function chunkContent(): Collection
    {
        return BookUtilities::parseStringToParagraphCollection($this->chapter->content->body)->chunkWhile(function ($value, $key, $chunk) {
            return $chunk->sum('length') + $value['length'] <= Pagination::RECOMMEND_MAX_LENGTH;
        });
    }


    public function publish($forceFireEvent = true): Closure
    {
        $event = Db::transaction(function () {
            $this->chapter->fill([
                'status' => ChapterStatus::PUBLISHED,
                'published_at' => Carbon::now(),
            ]);
            $this->chapter->save();

            return fn() => Event::fire('books.chapter.published', [$this->chapter]);
        });
        if ($forceFireEvent) {
            $event();
        }

        return $event;
    }

    public static function audit(): void
    {
        Db::transaction(function () {
            return Chapter::query()
                ->planned()
                ->where('published_at', '<=', Carbon::now())
                ->lockForUpdate()
                ->get()
                ->map(function ($chapter) {
                    return $chapter->service()->publish(forceFireEvent: false);
                });
        })->map(fn($callback) => is_callable($callback) ? $callback() : $callback);
    }
}
