<?php
declare(strict_types=1);

namespace Books\Book\Classes\Services;

use Books\Book\Classes\Contracts\iAudioFileFormatLengthMeter;

class AudioFileAACLengthMeter implements iAudioFileFormatLengthMeter
{
    protected string $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function getDuration(): int
    {
        return (int) self::getDurationOfWavInMs();
    }

    private function getDurationOfWavInMs(): float|int|string
    {
        $time = self::getDurationOfWav();

        list($hms, $milli) = explode('.', $time);
        list($hours, $minutes, $seconds) = explode(':', $hms);
        $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;

        return ($totalSeconds * 1000) + $milli;
    }

    /**
     * @return string
     */
    private function getDurationOfWav() {
        $cmd = "ffmpeg -i " . escapeshellarg($this->filename) . " 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//";

        return exec($cmd);
    }
}