<?php

namespace Books\Book\Classes\Exceptions;

use Exception;

class TextContentLinkDomainException extends Exception
{
    protected $message = 'Ссылка содержит недопустимый адрес. Разрешаются ссылки только на внутренние страницы сервиса';
}
