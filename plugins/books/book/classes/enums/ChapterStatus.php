<?php

namespace Books\Book\Classes\Enums;

enum ChapterStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Черновик',
            self::PUBLISHED => 'Опубликована',
        };
    }
}
