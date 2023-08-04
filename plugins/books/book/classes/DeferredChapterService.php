<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\ContentStatus;
use Books\Book\Classes\Enums\ContentTypeEnum;
use Books\Book\Models\Chapter;
use Illuminate\Support\Collection;
use ValidationException;

class DeferredChapterService extends ChapterService
{
    protected function create(array $data): Chapter
    {
        $new = parent::create(array_replace($data, ['content' => ''])); // Создать новую часть
        $new->deferredContentNew()->create(['type' => ContentTypeEnum::DEFERRED_CREATE, 'body' => $data['content']]);
        return $new;
    }

    /**
     * @throws ValidationException
     */
    protected function update(array $data): Chapter
    {
        return parent::update($this->dataPrepare($data));
    }

    protected function dataPrepare(array|Collection $data): array
    {
        return [
            'deferred_content' => $data['new_content'] ?? $data['content'] ?? ''
        ];
    }

    public function delete()
    {
        $content = $this->chapter->deletedContent()->firstOrCreate(['type' => ContentTypeEnum::DEFERRED_DELETE, 'status' => ContentStatus::Pending]);
        return $content->service()->markRequested();
    }

    public function mergeDeferred(): Chapter|bool
    {
        if ($content = $this->chapter->deferredContentOpened) {
            return parent::update(['new_content' => $content->body]);
        }
        return false;
    }
    public function markCanceledDeferredUpdate()
    {
        return $this->chapter->deferredContentOpened?->service()->markCanceled();
    }

    public function markCanceledDeletedContent()
    {
        return $this->chapter->deletedContent?->service()->markCanceled();
    }
}
