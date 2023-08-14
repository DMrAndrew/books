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
        parent::__construct(trans('books.breadcrumbs::lang.exceptions.duplicate', ['name' => $name]));
    }
}
