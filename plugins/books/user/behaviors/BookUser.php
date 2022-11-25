<?php

namespace Books\User\Behaviors;

use RainLab\User\Models\User;
use October\Rain\Extension\ExtensionBase;

class BookUser extends ExtensionBase
{
    private array $extraFillable = ['birthday', 'required_post_register'];

    public function __construct(protected User $parent)
    {
        $this->parent->addValidationRule('birthday', 'nullable');
        $this->parent->addValidationRule('birthday', 'date');
        $this->extendFillable();
        $this->parent->addCasts(['favorite_genres' => 'array']);

        //TODO перевод для birthday
    }

    private function extendFillable(): void
    {
        collect($this->extraFillable)->each(fn($i) => $this->parent->addFillable($i));
    }


}
