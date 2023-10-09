<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\ContentStatus;
use Books\Book\Classes\Enums\ContentTypeEnum;
use Books\Book\Models\Content;

/**
 * Для вывода инфы на странице книги
 */
class ContentInfoHelper
{
    public function __construct(public Content $deferred)
    {
    }

    public function isVisible(): bool
    {
        return $this->isBadDelete();
    }

    public function label(): string
    {
        return sprintf('%s главы', $this->deferred->type->label());
    }

    public function length(): bool|int
    {
        if ($this->deferred->type === ContentTypeEnum::DEFERRED_DELETE) {
            return false;
        }
        return $this->deferred->length;
    }

    public function statusTypeValue(): int
    {
        return $this->deferred->status->value;
    }

    public function typeValue(): int
    {
        return $this->deferred->type->value;
    }

    public function requestAllowed(): bool
    {
        if ($this->isBadDelete()) {
            return false;
        }
        return $this->deferred->allowedMarkAs(ContentStatus::Pending);
    }

    public function cancelAllowed(): bool
    {
        return $this->deferred->allowedMarkAs(ContentStatus::Cancelled);
    }

    public function isBadDelete(): bool
    {
        return $this->deferred->type === ContentTypeEnum::DEFERRED_DELETE && $this->deferred->status !== ContentStatus::Pending;
    }

    public function cancelMessage(): string
    {
        return sprintf('Отменить запрос на %s главы?', mb_strtolower($this->deferred->type->label()));
    }
}
