<?php

namespace Books\Book\Classes\Contracts;

use Books\Book\Classes\Enums\ContentTypeEnum;
use Books\Book\Classes\Exceptions\UnknownFormatException;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
use Closure;
use Exception;
use Illuminate\Support\Collection;
use ValidationException;

/**
 *
 */
interface iChapterService
{
    /**
     * @return bool
     */
    public function isNew(): bool;

    /**
     * @param Edition $edition
     * @return static
     */
    public function setEdition(Edition $edition): iChapterService;

    /**
     * @return Edition|null
     */
    public function getEdition(): ?Edition;

    /**
     * @return Chapter
     */
    public function getChapter(): Chapter;

    /**
     * @throws UnknownFormatException
     * @throws Exception
     */
    public function from(mixed $payload): ?Chapter;

    /**
     * @param string $content
     * @return bool|int
     */
    public function initUpdateBody(string $content): bool|int;

    /**
     * @throws ValidationException
     */
    public function delete(): bool;
    public function merge(ContentTypeEnum $type): Chapter|bool;
    public function markCanceled(ContentTypeEnum $type);
    public function markCanceledDeferredUpdate();
    public function markCanceledDeletedContent();

    /**
     * @param int $page
     * @return null
     */
    public function getPaginationLinks(int $page = 1);

    /**
     * @return void
     */
    public function paginate(): void;

    /**
     * @return Collection
     */
    public function chunkContent(): Collection;

    /**
     * @param bool $forceFireEvent
     * @return Closure
     */
    public function publish(bool $forceFireEvent = true): Closure;
}
