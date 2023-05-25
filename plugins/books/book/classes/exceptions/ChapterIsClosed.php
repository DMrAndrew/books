<?php

namespace Books\Book\Classes\Exceptions;

use Exception;

class ChapterIsClosed extends Exception
{
    protected $message = 'Фрагмент не доступен.';
}
