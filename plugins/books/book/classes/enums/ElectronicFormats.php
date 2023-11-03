<?php

namespace Books\Book\Classes\Enums;

use Books\Book\Classes\Converters\BaseConverter;
use Books\Book\Classes\Converters\FB2;
use Books\Book\Models\Book;

enum ElectronicFormats: string
{
    case FB2 = 'fb2';
    case EPUB = 'epub';
    case MOBI = 'mobi';
    case PDF = 'pdf';
    case TXT = 'txt';

    public function label(): string
    {
        return match ($this) {
            self::FB2 => 'fb2',
            self::EPUB => 'epub',
            self::MOBI => 'mobi',
            self::PDF => 'pdf',
            self::TXT => 'txt',
        };
    }

    public function converter(Book $book): BaseConverter
    {
        return new FB2($book);
        //        return new (match ($this) {
        //            self::FB2 => FB2::class,
        //            self::EPUB => throw new \Exception('To be implemented'),
        //            self::MOBI => throw new \Exception('To be implemented'),
        //            self::PDF => throw new \Exception('To be implemented'),
        //            self::TXT => throw new \Exception('To be implemented'),
        //        })($book);
    }

    public static function default(): ElectronicFormats
    {
        return self::FB2;
    }
}
