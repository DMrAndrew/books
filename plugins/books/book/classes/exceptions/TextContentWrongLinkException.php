<?php

namespace Books\Book\Classes\Exceptions;

use Exception;

class TextContentWrongLinkException extends Exception
{
    protected $message = 'Пустая ссылка или ссылка с некорректным адресом.';
}
