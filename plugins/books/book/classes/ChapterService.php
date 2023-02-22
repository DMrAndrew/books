<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\ChapterSalesType;
use Books\Book\Classes\Enums\ChapterStatus;
use Books\Book\Classes\Exceptions\UnknownFormatException;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
use Books\Book\Models\Pagination;
use Carbon\Carbon;
use Db;
use Event;
use Exception;
use Html;
use Illuminate\Support\Collection;
use Str;
use DOMDocument;

class ChapterService
{
    public function __construct(protected Chapter $chapter, protected ?Edition $edition = null)
    {
    }

    public function isNew(): bool
    {
        return !$this->chapter?->id;
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
            return $this->create([
                'title' => $payload->getTitle(),
                'content' => $payload->getContent(),
                'status' => ChapterStatus::PUBLISHED,
            ]);
        }
        if (is_array($payload)) {

            return $this->{$this->isNew() ? 'create' : 'update'}($payload);
        }
        throw new UnknownFormatException();
    }

    /**
     * @throws Exception
     */
    protected function create(array $data): Chapter
    {
        if (!$this->edition) {
            throw new Exception('Edition required.');
        }
        $this->chapter->fill($this->dataPrepare($data));
        $this->chapter->sort_order = $this->edition->nextChapterSortOrder();
        $this->chapter->sales_type ??= ChapterSalesType::PAY;
        $this->chapter['edition_id'] = $this->edition->id;
        $this->chapter->save();
        Event::fire('books.chapter.created', [$this->chapter]);

        return $this->chapter;
    }

    protected function update(array $data): Chapter
    {
        return Db::transaction(function () use ($data) {
            $data = $this->dataPrepare($data);
            $this->chapter->fill($data);
            $this->chapter->save();
            Event::fire('books.chapter.updated', [$this->chapter]);

            return $this->chapter;
        });
    }

    public function dataPrepare(array|Collection $data): array
    {
        $data = collect($data);

        if ($data->has('status')) {
            $data['status'] = $data['status'] instanceof ChapterStatus ? $data['status'] : (ChapterStatus::tryFrom($data['status']) ?? ChapterStatus::DRAFT);

            if ($data->get('status') instanceof ChapterStatus) {
                if ($data['status'] === ChapterStatus::PUBLISHED && !isset($data['published_at'])) {
                    $data['published_at'] = Carbon::now();
                }

                if ($data['status'] === ChapterStatus::DRAFT) {
                    $data['published_at'] = null;
                }
            } else {
                $data->forget('status');
            }
        }

        if ($data->has('content')) {
            $data['new_content'] = Html::clean($data['content']);
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

    public function paginate()
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
            $page = $this->chapter->pagination()->firstOrCreate(['page' => $paginator->page],['length' => $paginator->length]);
            $page->fill($paginator->toArray());
            $page->save();
            return $page;
        });
        $this->chapter->pagination()->whereNotIn('id', $pagination->pluck('id'))->delete();
        $this->chapter->pagination()->get()->each->setNeighbours();
        $this->chapter->edition->lengthRecount();
        $this->chapter->setNeighbours();
        Event::fire('books.chapter.paginated');

        return $pagination;
    }

    public function chunkContent()
    {
        $dom = (new DOMDocument());
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($this->chapter->content->body, 'HTML-ENTITIES', 'UTF-8'));
        $root = $dom->getElementsByTagName('body')[0];
        $perhapses = collect($root->childNodes)->map(fn($node) => [
            'html' => $dom->saveHTML($node),
            'length' => strlen(Str::squish($node->textContent)),
        ]);

        return $perhapses->chunkWhile(function ($value, $key, $chunk) {
            return $chunk->sum('length') + $value['length'] <= Pagination::RECOMMEND_MAX_LENGTH;
        });


    }
}
