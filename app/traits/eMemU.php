<?php

namespace App\traits;

trait eMemU
{
    protected $memory = 0;

    public function echoMemoryUsage(string $string = ''): void
    {
        $memory = round((memory_get_usage() - $this->memory) / 1024 / 1024, 2);
        echo sprintf('Memory usage %s: %s MB', $string, $memory);
        echo PHP_EOL;
    }

}
