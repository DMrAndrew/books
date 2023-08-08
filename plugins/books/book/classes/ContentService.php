<?php

namespace Books\Book\Classes;

use Backend;
use Books\Book\Classes\Enums\ContentStatus;
use Books\Book\Models\Chapter;
use Books\Book\Models\Content;
use Closure;
use Db;
use Event;
use Exception;
use Mail;
use ValidationException;

class ContentService
{
    public function __construct(public Content $content)
    {
    }

    /**
     * @throws ValidationException
     */
    public function markMerged(?string $comment = null): bool
    {
        $this->validateContent();

        if (!$this->content->allowedMarkAs(ContentStatus::Merged)) {
            throw new ValidationException(['status' => 'Действие не разрешено']);
        }

        /**
         * @var DeferredChapterService $service
         */
        $service = $this->content->contentable->deferredService();
        return Db::transaction(function () use ($service, $comment): Closure {

            if ($service->merge($this->content->type) && $this->content->markMerged($comment)) {
                return function () use ($comment): bool {
                    Event::fire('books.book::content.deferred.merged', [$this->content, $comment]);
                    return true;
                };
            }
            return fn(): bool => false;
        })();

    }

    /**
     * @throws ValidationException
     * @throws Exception
     */
    public function markCanceled(): bool
    {
        if (!$this->content->allowedMarkAs(ContentStatus::Cancelled)) {
            throw new ValidationException(['status' => 'Действие не разрешено']);
        }
        return $this->content->markCanceled();

    }

    /**
     * @param string|null $comment
     * @return bool
     * @throws ValidationException
     */
    public function markRejected(?string $comment = null): bool
    {
        $this->validateContent();

        if (!$this->content->allowedMarkAs(ContentStatus::Rejected)) {
            throw new ValidationException(['status' => 'Действие не разрешено']);
        }

        if ($this->content->markRejected($comment)) {
            Event::fire('books.book::content.deferred.rejected', [$this->content, $comment]);
            return true;
        };

        return false;
    }

    /**
     * @param string|null $comment
     * @return bool
     */
    public function markRequested(?string $comment = null): bool
    {
        if ($this->content->allowedMarkAs(ContentStatus::Pending) && $this->content->markRequested($comment)) {
            if ($this->contentableIsChapter()) {
                $this->sendRequestedMailNotify($comment);
            }

            return true;
        }

        return false;
    }

    /**
     * @throws ValidationException
     */
    protected function validateContent(): bool
    {
        return $this->contentableIsChapter() ?: throw new ValidationException(['status' => 'Контент не принадлежит главе']);
    }

    protected function contentableIsChapter(): bool
    {
        return $this->content->contentable instanceof Chapter;
    }

    /**
     * @param string|null $comment
     * @return void
     */
    public function sendRequestedMailNotify(?string $comment = null): void
    {
        if ($recipients = Backend\Models\UserGroup::where('code', 'owners')->with('users')->first()?->users->map->email->toArray()) {

            /**
             * @var Chapter $chapter
             */
            $chapter = $this->content->contentable;
            $data = [
                'nickname' => $chapter->edition->book->author->profile->username,
                'title' => strip_tags($chapter->title),
                'book' => $chapter->edition->book->title,
                'type_label' => $this->content->type->label(),
                'content' => $this->content,
                'comment' => $comment,
                'backend_url' => Backend::url(sprintf("books/book/content/update/%s", $this->content->id)),
            ];
            Mail::queue(
                'books.book::mail.deferred_request',
                $data,
                fn($msg) => $msg->to($recipients)
            );
        }
    }
}
