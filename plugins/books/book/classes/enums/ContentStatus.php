<?php

namespace Books\Book\Classes\Enums;

enum ContentStatus:int
{
    case Pending = 0;
    case Approved = 1;
    case Rejected = 2;
    case Merged = 3;
    case Conflict = 4;
    case Error = 5;
    case Unknown = 6;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает проверки',
            self::Approved => 'Approved',
            self::Rejected => 'Отклонён',
            self::Merged => 'Слияние выполнено',
            self::Conflict => 'Conflict',
            self::Error => 'Error',
            self::Unknown => 'Unknown',
            default => ''
        };
    }
}
