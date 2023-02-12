<?php

namespace Books\Book\Classes\Enums;

enum EditionsEnums: int
{
    case Ebook = 1;

    case Audio = 2;
    case Physic = 3;
    case Comics = 4;

    public static function default(): EditionsEnums
    {
        return self::Ebook;
    }
}
