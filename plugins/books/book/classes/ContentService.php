<?php

namespace Books\Book\Classes;

use Backend;
use Books\Book\Models\Content;
use Event;
use Mail;
use ValidationException;

class ContentService
{
    public function __construct(public Content $content)
    {
    }

    public function markCanceled(): bool
    {
        if (!$this->content->canBeCanceled()) {
            throw new ValidationException(['status' => 'Запрос не может быть отменён']);
        }
        return $this->content->markCanceled();

    }

    public function markRejected(?string $comment = null): bool
    {
        if (!$this->content->markRejected($comment)) {
            return false;
        };
        Event::fire('books.book::content.deferred.rejected', [$this->content, $comment]);
        return true;
    }

    public function markRequested(?string $comment = null): bool
    {
        if (!$this->content->markRequested($comment)) {
            return false;
        }
        if ($recipients = Backend\Models\UserGroup::where('code', 'owners')->first()?->users->map->email->toArray()) {
            $chapter = $this->content->contentable;
            $data = [
                'nickname' => $chapter->edition->book->author->profile->nickname,
                'title' => BookUtilities::stringToDiDom($chapter->contentable->title)->text(),
                'book' => $chapter->edition->book->title,
                'type' => $this->content->type,
                'content' => $this,
                'comment' => $comment,
                'backend_url' => Backend::url(sprintf("books/book/content/update/%s", $this->content->id)),
            ];
            Mail::queue(
                'books.book::mail.deferred_request',
                $data,
                fn($msg) => $msg->to($recipients)
            );
        }
        return true;
    }
}
