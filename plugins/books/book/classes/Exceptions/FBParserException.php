<?php
namespace Books\Book\Classes\Exceptions;

use Exception;

class FBParserException extends Exception
{
    protected $message = 'Не удалось выполнить импорт. Файл поврежден или некорректно сформирован.';
}
