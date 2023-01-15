<?php

namespace Books\Book\Classes;

use Db;
use Html;
use Event;
use Carbon\Carbon;
use Books\Book\Models\Chapter;
use Illuminate\Support\Collection;
use Books\Book\Models\EbookEdition;
use Books\Book\Models\ChapterStatus;
use Illuminate\Database\Eloquent\Model;
use Books\Book\Models\ChapterSalesType;

class ChapterManager
{
    /**
     * @param EbookEdition $ebook
     * @param bool $should_recompute_ebook
     */
    public function __construct(protected EbookEdition $ebook, protected bool $should_recompute_ebook = true)
    {
    }

    /**
     * @param bool $should_recompute_ebook
     */
    public function setShouldRecomputeEbook(bool $should_recompute_ebook): void
    {
        $this->should_recompute_ebook = $should_recompute_ebook;
    }

    public function create(array $data): Model
    {
        return Db::transaction(function () use ($data) {
            $data = $this->dataPrepare($data);
            $data['sales_type'] ??= ChapterSalesType::PAY;
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = $this->ebook->nextChapterSortOrder();
            }
            $chapter = $this->ebook->chapters()->create($data);
            if ($this->should_recompute_ebook) {
                $this->ebook->recompute();
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
            if ($this->should_recompute_ebook) {
                $this->ebook->recompute();
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
        }
        $data['length'] = Chapter::countChapterLength($data['content']??'');

        return $data->toArray();
    }
}
