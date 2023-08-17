<?php

namespace Books\Breadcrumbs\Exceptions;

/**
 * Class InvalidBreadcrumbException
 * @package Books\Breadcrumbs\Exceptions
 */
class InvalidBreadcrumbException extends BreadcrumbsException
{
    public function __construct($name)
    {
        parent::__construct("Хлебная крошка с именем {$name} не найдена ");
    }
}
