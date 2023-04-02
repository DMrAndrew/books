<?php

namespace App\classes;

use October\Rain\Extension\ExtensionBase;
use System\Models\Revision;

class RevisionHistory extends ExtensionBase
{
    public function __construct(protected Revision $revision)
    {
    }

    public function odds(): int
    {
        return (int) $this->revision->new_value - (int) $this->revision->old_value;
    }
}
