<?php
declare(strict_types=1);

namespace Books\Book\Classes\Contracts;

interface iAudioFileListenTokenService
{
    public static function generateListenTokenForUser() :void;

    public static function validateListenTokenForUser() :bool;
}