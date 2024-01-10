<?php

namespace App\traits;

use Books\Book\Classes\FB2Manager;
use October\Rain\Extension\ExtensionBase;
use System\Models\File;

class FileExtension extends ExtensionBase
{
    public function __construct(public File $file)
    {
    }

    public function fbParser(): FB2Manager
    {
        return new FB2Manager($this->file);
    }
}
