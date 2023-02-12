<?php

namespace Books\Book\Classes\Exceptions;

class UnknownFormatException extends \Exception
{
    protected $message = 'Неизвестный формат данных.';
}
