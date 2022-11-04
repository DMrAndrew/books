<?php

namespace Books\User\Behaviors;

use October\Rain\Extension\ExtensionBase;

class BookUser extends ExtensionBase
{
    public function __construct(protected $parent)
    {
        $this->parent->addValidationRule('birthday', 'required');
        $this->parent->addValidationRule('birthday', 'date');
        $this->parent->addFillable('birthday');

        //TODO перевод для birthday
    }
}
