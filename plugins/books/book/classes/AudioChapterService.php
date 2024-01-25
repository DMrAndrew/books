<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Contracts\iAudioChapterService;
use Books\Book\Classes\Contracts\iChapterService;
use Books\Book\Classes\Enums\ContentTypeEnum;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
use Closure;

class AudioChapterService implements iAudioChapterService
{
    public function isNew(): bool
    {
        // TODO: Implement isNew() method.
    }

    public function setEdition(Edition $edition): iChapterService
    {
        // TODO: Implement setEdition() method.
    }

    public function getEdition(): ?Edition
    {
        // TODO: Implement getEdition() method.
    }

    public function getChapter(): Chapter
    {
        // TODO: Implement getChapter() method.
    }

    public function from(mixed $payload): ?Chapter
    {
        // TODO: Implement from() method.
    }

    public function initUpdateBody(string $content): bool|int
    {
        // TODO: Implement initUpdateBody() method.
    }

    public function delete(): bool
    {
        // TODO: Implement delete() method.
    }

    public function merge(ContentTypeEnum $type): Chapter|bool
    {
        // TODO: Implement merge() method.
    }

    public function markCanceled(ContentTypeEnum $type)
    {
        // TODO: Implement markCanceled() method.
    }

    public function publish(bool $forceFireEvent = true): Closure
    {
        // TODO: Implement publish() method.
    }
}
