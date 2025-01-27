<?php

namespace Books\Book\Classes\Enums;

use Books\Book\Classes\Converters\BaseConverter;
use Books\Book\Classes\Converters\Epub;
use Books\Book\Classes\Converters\FB2;
use Books\Book\Classes\Converters\MOBI;
use Books\Book\Classes\Converters\PDF;
use Books\Book\Classes\Converters\TXT;
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
        return new (match ($this) {
            self::FB2 => FB2::class,
            self::EPUB => Epub::class,
            self::MOBI => MOBI::class,
            self::PDF => PDF::class,
            self::TXT => TXT::class,
        })($book);
    }

    public static function default(): ElectronicFormats
    {
        return self::FB2;
    }
}
