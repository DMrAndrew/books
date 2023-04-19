<?php

namespace Books\Book\Classes\Enums;

enum ChapterStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case PLANNED = 'planned';
    case PENDING = 'pending';

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Черновик',
            self::PUBLISHED => 'Опубликована',
            self::PLANNED => 'Опубликуется',
            self::PENDING => 'Загружается',
        };
    }
}
