<?php

namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
use Books\Book\Models\Pagination;
use Books\Collections\classes\CollectionEnum;
use Event;
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

    public function __construct(protected Book $book, protected ?Chapter $chapter, protected ?int $page = 1, protected ?User $user = null)
    {
        //TODO refactor
        $this->user ??= Auth::getUser();
        $this->page ??= 1;
        $this->edition = $this->book->ebook;
        $this->chapters = $this->edition->chapters;
        $this->chapter = $this->edition->chapters()->find($this->chapter?->id) ?? $this->chapters->first();
        $this->setPage($this->page);
    }

    /**
     * @param int|null $page
     */
    public function setPage(?int $page): void
    {
        $this->page = $page;
        $this->paginator = $this->chapter?->pagination()->page($this->page)?->first() ?? abort(404);
    }

    public function track(?int $ms, int $paginator_id)
    {
        if (!$this->user) {
            return null;
        }

        $sec = (int)floor(($ms ?? 0) / 1000);
        if ($paginator = $this->chapter?->pagination()->find($paginator_id)) {
            if ($tracker = $paginator->trackByUser($this->user)) {
                $tracker->update(['time' => $tracker->time + $sec, 'length' => $paginator->length, 'progress' => 100]);
                Event::fire('books.paginator.tracked');
                $paginator->chapter->progress($this->user);
                return $tracker;
            }
        }
    }

    public function getReaderPage(): array
    {
        return [
            'book' => $this->book->newQuery()->defaultEager()->find($this->book->id),
            'pagination' => [
                'prev' => !!($this->prevPage() ?? $this->prevChapter()),
                'links' => $this->chapter->service()->getPaginationLinks($this->page),
                'next' => !!($this->nextPage() ?? $this->nextChapter()),
            ],
            'chapters' => $this->chapters,
            'reader' => [
                'chapter' => $this->chapter,
                'paginator' => $this->paginator,
            ],
        ];
    }

    /**
     * @return Pagination|null $pagination
     */
    public function nextPage(): ?Pagination
    {
        return $this->paginator->next;
    }

    /**
     * @return Pagination|null $pagination
     */
    public function prevPage(): ?Pagination
    {
        return $this->paginator->prev;
    }

    /**
     * @return Chapter|null $chapter
     */
    public function nextChapter(): ?Chapter
    {
        return $this->chapter->next;
    }

    /**
     * @return Chapter|null $chapter
     */
    public function prevChapter(): ?Chapter
    {
        return $this->chapter->prev;
    }
}