<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Exceptions\ChapterIsClosed;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
use Books\Book\Models\Pagination;
use Books\Collections\classes\CollectionEnum;
use Illuminate\Database\Eloquent\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

class ReaderAudio
{
    protected Edition $edition;

    protected Collection $chapters;

    protected Pagination $paginator;

    protected bool $contentGuard = true;

    public function __construct(
        protected Book $book,
        protected ?Chapter $chapter = null,
        protected ?User $user = null
    )
    {
        $this->user ??= Auth::getUser();
        $this->book = Book::query()->withChapters()->defaultEager()->find($this->book->id)
            ?? $this->user?->profile->books()->withChapters()->defaultEager()->find($this->book->id)
            ?? abort(404);
        $this->page ??= 1;
        $this->edition = $this->book->audiobook;
        $this->chapters = $this->edition->chapters()->whereHas('audio')->get();
        $this->chapter = $this->edition->chapters()->with('audio')->find($this->chapter?->id) ?? $this->chapters->first();
    }

    /**
     * @throws ChapterIsClosed
     */
//    public function setPage(?int $page): void
//    {
//        $this->page = $page;
//        $this->paginator = $this->chapter?->pagination()->page($this->page)?->first() ?? abort(404);
//    }

    public function isPageAllowed(): bool
    {
        return ! $this->contentGuard
            || $this->chapter->isFree()
            || ($this->user && $this->book->isAuthor($this->user->profile))
            || ($this->user && $this->user->bookIsBought($this->edition));
    }

    public function readBtn(): bool
    {
        return ! $this->nextChapter()
            && $this->book->audiobook->status === BookStatus::COMPLETE
            && ($this->user && ! $this->user->library($this->book)->is(CollectionEnum::READ));
    }

    /**
     * @throws ChapterIsClosed
     */
    public function getReaderPage(): array
    {
        if (! $this->isPageAllowed()) {
            throw new ChapterIsClosed();
        }

        return [
            'pagination' => [
                'prev' => (bool) ($this->prevChapter()),
                'next' => (bool) ($this->nextChapter()),
                'read' => $this->readBtn(),
            ],
            'chapters' => $this->chapters,
            'reader' => [
                'chapter' => $this->chapter,
            ],
            'redirectIfJSIsOff' => ! (! $this->prevChapter()),
            'book' => $this->book->newQuery()->defaultEager()->find($this->book->id),
        ];
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
}
