<?php
declare(strict_types=1);

namespace Books\Blog\Classes\Enums;

enum VideoBlogPostStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case PLANNED = 'planned';

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Черновик',
            self::PUBLISHED => 'Опубликована',
            self::PLANNED => 'Опубликуется',
        };
    }
}
