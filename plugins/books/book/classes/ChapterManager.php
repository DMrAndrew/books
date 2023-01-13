<?php

namespace Books\Book\Classes;

use Db;
use Html;
use Event;
use Carbon\Carbon;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Illuminate\Support\Collection;
use Books\Book\Models\ChapterStatus;
use Books\Book\Models\ChapterEdition;
use Illuminate\Database\Eloquent\Model;

class ChapterManager
{
    /**
     * @param Book $book
     * @param bool $should_recompute_book
     */
    public function __construct(protected Book $book, protected bool $should_recompute_book = true)
    {
    }

    /**
     * @param bool $should_recompute_book
     */
    public function setShouldRecomputeBook(bool $should_recompute_book): void
    {
        $this->should_recompute_book = $should_recompute_book;
    }

    public function create(array $data): Model
    {
        return Db::transaction(function () use ($data) {
            $data = $this->dataPrepare($data);
            $data['edition'] ??= ChapterEdition::PAY;
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = $this->book->nextChapterSortOrder();
            }
            $chapter = $this->book->chapters()->create($data);
            if ($this->should_recompute_book) {
                $this->book->recompute();
            }
            Event::fire('books.chapter.created', [$chapter]);

            return $chapter;
        });
    }

    public function update(Chapter $chapter, $data): Chapter
    {
        return Db::transaction(function () use ($chapter, $data) {
            $data = $this->dataPrepare($data);
            $chapter->update($data);
            if ($this->should_recompute_book) {
                $this->book->recompute();
            }
            Event::fire('books.chapter.updated', [$chapter]);

            return $chapter;

        });
    }


    protected function dataPrepare(array|Collection $data): array
    {
        $data = is_array($data) ? collect($data) : $data;

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
            $data['content'] = Html::clean($data['content']);
            $data['length'] = Chapter::countChapterLength($data['content']);
        }

        return $data->toArray();
    }
}
