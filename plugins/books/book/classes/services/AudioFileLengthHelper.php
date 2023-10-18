<?php
declare(strict_types=1);

namespace Books\Book\Classes\Services;

use Exception;
use System\Models\File;

class AudioFileLengthHelper
{
    /**
     * @param File $file
     *
     * @return int|null
     */
    public static function getAudioLengthInSeconds(File $file): ?int
    {
        $lengthMeterClass = match($file->extension) {
            'mp3' => AudioFileMP3LengthMeter::class,
            'aac' => AudioFileAACLengthMeter::class,
            default => null
        };

        if (!$lengthMeterClass) {
            return null;
        }

        try {
            $filePath = $file->getLocalPath();
            $lengthMeter = new $lengthMeterClass($filePath);

            return $lengthMeter->getDuration();

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param File $file
     *
     * @return string
     */
    public static function getAudioLengthHumanReadable(File $file): string
    {
        $audioLenghInSeconds = self::getAudioLengthInSeconds($file);

        return self::formatSecondsToHumanReadableTime($audioLenghInSeconds);
    }

    /**
     * @param File $file
     *
     * @return string
     */
    public static function getAudioLengthHumanReadableShort(File $file): string
    {
        $audioLenghInSeconds = self::getAudioLengthInSeconds($file);

        return self::formatSecondsToHumanReadableTimeShort($audioLenghInSeconds);
    }

    /**
     * @param int|null $seconds
     *
     * @return string|null
     */
    public static function formatSecondsToHumanReadableTime(?int $seconds): ?string
    {
        if (!$seconds) {
            return null;
        }

        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");

        // day+
        if ($seconds > 24 * 60 * 60) {
            return $dtF->diff($dtT)->format('%a дней %h час %i мин %s с');
        }

        // hour+
        if ($seconds > 60 * 60) {
            return $dtF->diff($dtT)->format('%h час %i мин');
        }

        // minutes, seconds
        return $dtF->diff($dtT)->format('%i мин %s с');
    }

    /**
     * @param int|null $seconds
     *
     * @return string|null
     */
    public static function formatSecondsToHumanReadableTimeShort(?int $seconds): ?string
    {
        if (!$seconds) {
            return null;
        }

        // day+
        if ($seconds > 24 * 60 * 60) {
            return gmdate("d H:i:s", $seconds);
        }

        // hour+
        if ($seconds > 60 * 60) {
            return gmdate("H:i:s", $seconds);
        }

        // minutes, seconds
        return gmdate("i:s", $seconds);
    }
}