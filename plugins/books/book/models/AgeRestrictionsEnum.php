<?php

namespace Books\Book\Models;

enum AgeRestrictionsEnum: int
{
    case A0 = 0;
    case A6 = 6;
    case A12 = 12;
    case A16 = 16;
    case A18 = 18;

    public function getLabel(): string
    {
        return $this->value . '+';
    }

    public static function default(): AgeRestrictionsEnum
    {
        return self::A0;
    }
}
