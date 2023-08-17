<?php

namespace Books\Breadcrumbs\Exceptions;

/**
 * Class DuplicateBreadcrumbException
 * @package Books\Breadcrumbs\Exceptions
 */
class DuplicateBreadcrumbException extends BreadcrumbsException
{
    public function __construct($name)
    {
        parent::__construct("Имя хлебной крошки уже зарегистрировано {$name}");
    }
}
