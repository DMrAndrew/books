<?php

namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
use Books\Book\Models\Pagination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

class Reader
{
    protected Edition $edition;
    protected Collection $chapters;
    protected Collection $pagination;
    protected Pagination $paginator;
    protected Iterator $iteratorPagination;
    protected Iterator $iteratorChapters;

    public function __construct(protected Book $book, protected ?Chapter $chapter, protected ?int $page = 1, protected ?User $user = null)
    {
        //TODO refactor
        $this->user ??= Auth::getUser();
        $this->page ??= 1;
        $this->edition = $this->book->ebook;
        $this->chapters = $this->edition->chapters;
        $this->chapter = $this->edition->chapters()->find($this->chapter?->id) ?? $this->chapters->first();

        $this->pagination = $this->chapter->pagination;
        $this->iteratorPagination = new Iterator($this->pagination->pluck('page')->toArray());
        $this->iteratorChapters = new Iterator($this->chapter->pluck('id')->toArray());
        $this->iteratorChapters->seek($this->chapter->id);
        $this->setPage($this->page);
    }

    /**
     * @param int|null $page
     */
    public function setPage(?int $page): void
    {
        $this->page = $page;
        $this->paginator = $this->chapter->pagination()->page($this->page)?->first() ?? abort(404);
        $this->iteratorPagination->seek($this->page);
    }

    public function track(?int $ms): ?Model
    {
        if (!$this->user) {
            return null;
        }

        $sec = (int)floor(($ms ?? 0) / 1000);

        $tracker = $this->paginator->trackers()->firstOrCreate(['user_id' => $this->user->id], ['sec' => 1, 'length' => $this->paginator->length]);
        $tracker->update(['sec' => $tracker->sec + $sec]);
        return  $tracker;
    }

    public function getReaderPage(): array
    {
        return [
            'pagination' => [
                'prev' => $this->iteratorPagination->hasPrev() ?? $this->iteratorChapters->hasPrev(),
                'links' => $this->chapter->getPaginationLinks($this->page),
                'next' => $this->iteratorPagination->hasNext() ?? $this->iteratorChapters->hasNext()
            ],
            'chapters' => $this->chapters,
            'reader' => [
                'chapter' => $this->chapter,
                'paginator' => $this->paginator,
            ]
        ];
    }

    /**
     * @return false|int $page_number
     */

    public function nextPage(): bool|int
    {
        return $this->iteratorPagination->next();
    }

    /**
     * @return false|int $page_number
     */
    public function prevPage(): bool|int
    {
        return $this->iteratorPagination->prev();
    }

    /**
     * @return false|int $chapter_id
     */
    public function nextChapter(): bool|int
    {
        return $this->iteratorChapters->next();
    }

    /**
     * @return false|int $chapter_id
     */
    public function prevChapter(): bool|int
    {
        return $this->iteratorChapters->prev();
    }


}
