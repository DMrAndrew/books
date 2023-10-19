<?php
declare(strict_types=1);

namespace Books\Book\Classes\Services;

use Books\Book\Classes\Contracts\iAudioFileFormatLengthMeter;
use Owenoj\LaravelGetId3\GetId3;

class AudioFileAACLengthMeter implements iAudioFileFormatLengthMeter
{
    private string $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function getDuration(): int
    {
        $getID3 = new getID3($this->filename);
        $fileInfo = $getID3->extractInfo();

        if (isset($fileInfo['playtime_seconds'])) {
            return (int) $fileInfo['playtime_seconds'];
        }

        return 0;
    }
}