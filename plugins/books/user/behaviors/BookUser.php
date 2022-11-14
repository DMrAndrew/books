<?php

namespace Books\User\Behaviors;

use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class BookUser extends ExtensionBase
{
    public function __construct(protected User $parent)
    {
        $this->parent->addValidationRule('birthday', 'nullable');
        $this->parent->addValidationRule('birthday', 'date');
        $this->parent->addFillable('birthday');
        $this->parent->addFillable('required_post_register');

        //TODO перевод для birthday
    }


}
