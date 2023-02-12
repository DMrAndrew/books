<?php

namespace Books\Book\Classes\Exceptions;

use ApplicationException;

class FBParserException extends ApplicationException
{
    protected $message = 'Не удалось выполнить импорт. Файл поврежден или некорректно сформирован.';
}
