<?php

namespace MOBIClass;

enum ConstantsEnum: int
{
    case NO_COMPRESSION = 1;
    case PALMDOC_COMPRESSION = 2;
    case HUFF = 3;
    case RECORD_SIZE = 4;
    case NO_ENCRYPTION = 5;
    case MOBIPOCKET_BOOK = 6;
    case CP1252 = 7;
    case UTF8 = 8;

    public function value(): int
    {
        return match ($this) {
            self::NO_COMPRESSION => 1,
            self::PALMDOC_COMPRESSION => 2,
            self::HUFF => 17480,
            self::RECORD_SIZE => 4096,
            self::NO_ENCRYPTION => 0,
            self::MOBIPOCKET_BOOK => 2,
            self::CP1252 => 1252,
            self::UTF8 => 65001,
        };
    }
}
