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
        parent::__construct(trans('breadcrumbs.breadcrumbs::lang.exceptions.invalid', ['name' => $name]));
    }
}
