<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\ContentStatus;
use Books\Book\Classes\Enums\ContentTypeEnum;
use Books\Book\Models\Chapter;
use Books\Book\Models\Content;
use Db;
use Illuminate\Support\Collection;

class DeferredChapterService extends ChapterService
{
    protected function create(array $data): Chapter
    {
        return Db::transaction(function () use ($data) {
            $new = parent::create(array_replace($data, ['new_content' => '']));
            $new->deferred()->type(ContentTypeEnum::DEFERRED_CREATE)->create(['type' => ContentTypeEnum::DEFERRED_CREATE, 'body' => $data['new_content']]);
            return $new;
        });
    }


    protected function dataPrepare(array|Collection $data): array
    {
        $data = parent::dataPrepare($data);
        if ($this->isNew()) {
            return $data;
        }
        return [
            'deferred_content' => $data['new_content'] ?? null,
            'title' => $data['title']
        ];
    }

    public function delete(): bool
    {
        return Db::transaction(function () {
            /**
             * @var Content $content
             */
            $content = $this->chapter->deferred()->type(ContentTypeEnum::DEFERRED_DELETE)
                ->firstOrCreate(['type' => ContentTypeEnum::DEFERRED_DELETE, 'status' => ContentStatus::Pending]);
            return $content->service()->markRequested();
        });
    }

    public function merge(ContentTypeEnum $type): Chapter|bool
    {
        /**
         * @var Content $content
         */
        if ($content = $this->chapter->deferred()->type($type)->first()) {
            return match ($type) {
                ContentTypeEnum::DEFERRED_UPDATE, ContentTypeEnum::DEFERRED_CREATE => (parent::update(['new_content' => $content->body])),
                ContentTypeEnum::DEFERRED_DELETE => parent::delete(),
            };
        }
        return false;
    }

    public function markCanceled(ContentTypeEnum $type)
    {
        return $this->chapter
            ->deferred()
            ->type($type)
            ->first()?->service()
            ->markCanceled();
    }

    public function markCanceledDeferredUpdate()
    {
        return $this->markCanceled(ContentTypeEnum::DEFERRED_UPDATE);
    }

    public function markCanceledDeletedContent()
    {
        return $this->markCanceled(ContentTypeEnum::DEFERRED_DELETE);
    }
}
