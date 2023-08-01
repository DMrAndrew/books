<?php

namespace Books\Book\Classes;

use Backend;
use Books\Book\Classes\Enums\ContentStatus;
use Books\Book\Classes\Enums\ContentTypeEnum;
use Books\Book\Models\Chapter;
use Books\Book\Models\Content;
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
    public function markMerged(?string $comment = null): Chapter|bool
    {
        if(!$this->content->allowedMarkAs(ContentStatus::Merged)){
            throw new ValidationException(['status' => 'Действие не разрешено']);
        }
        if (!($this->content->contentable instanceof Chapter)) {
            return false;
        }

        /**
         * @var ChapterService $service
         */
        $service = $this->content->contentable->service();
        return Db::transaction(function () use ($service, $comment) {
            $merged = match ($this->content->type) {
                ContentTypeEnum::DEFERRED_UPDATE => $service->mergeDeferred(),
                ContentTypeEnum::DEFERRED_DELETE => $service->actionDelete()
            };
            if ($merged && $this->content->markMerged($comment)) {
                Event::fire('books.book::content.deferred.merged', [$this->content, $comment]);
            }
            return $merged;
        });

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
        if(!$this->content->allowedMarkAs(ContentStatus::Rejected)){
            throw new ValidationException(['status' => 'Действие не разрешено']);
        }
        if (!($this->content->contentable instanceof Chapter) || !$this->content->markRejected($comment)) {
            return false;
        };
        Event::fire('books.book::content.deferred.rejected', [$this->content, $comment]);
        return true;
    }

    /**
     * @param string|null $comment
     * @return bool
     */
    public function markRequested(?string $comment = null): bool
    {
        if (!$this->content->allowedMarkAs(ContentStatus::Pending) || !$this->content->markRequested($comment)) {
            return false;
        }
        if ($this->content->contentable instanceof Chapter) {
            $this->sendRequestedMailNotify($comment);
        }

        return true;
    }

    /**
     * @param string|null $comment
     * @return void
     */
    public function sendRequestedMailNotify(?string $comment = null): void
    {
        if ($recipients = Backend\Models\UserGroup::where('code', 'owners')->first()?->users->map->email->toArray()) {

            /**
             * @var Chapter $chapter
             */
            $chapter = $this->content->contentable;
            $data = [
                'nickname' => $chapter->edition->book->author->profile->username,
                'title' => strip_tags($chapter->title),
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
    }
}
