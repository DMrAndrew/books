<?php

namespace Books\Book\Classes\Exceptions;

use Exception;

class DownloadNotAllowed extends Exception
{
    protected $message = 'Скачивание книги не разрешено автором.';
}
