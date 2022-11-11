<?php

namespace Books\User\Behaviors;

use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class BookUser extends ExtensionBase
{
    public function __construct(protected User $parent)
    {
        $this->parent->addValidationRule('birthday', 'required');
        $this->parent->addValidationRule('birthday', 'date');
        $this->parent->addFillable('birthday');

        //TODO перевод для birthday
    }
}
