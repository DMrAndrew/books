<?php
declare(strict_types=1);

namespace Books\AuthorPrograms\Classes\Enums;

enum ProgramsEnums: string
{
    case AUTHOR_BIRTHDAY = 'author_birthday';
    case READER_BIRTHDAY = 'reader_birthday';
    case NEW_READER = 'new_reader';
    case REGULAR_READER = 'regular_reader';
}
