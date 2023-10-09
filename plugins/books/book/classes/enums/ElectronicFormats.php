<?php

namespace Books\Book\Classes\Enums;

enum ElectronicFormats: string
{
    case FB2 = 'fb2';
    case EPUB = 'epub';
    case MOBI = 'mobi';
    case PDF = 'pdf';
    case TXT = 'txt';
}
