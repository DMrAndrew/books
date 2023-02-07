<?php

/**
 * проверяем, что функция mb_ucfirst не объявлена
 * и включено расширение mbstring (Multibyte String Functions)
 */

use RainLab\User\Facades\Auth;

if (!function_exists('mb_ucfirst') && extension_loaded('mbstring')) {
    /**
     * mb_ucfirst - преобразует первый символ в верхний регистр
     * @param string $str - строка
     * @param string $encoding - кодировка, по-умолчанию UTF-8
     * @return string
     */
    function mb_ucfirst($str, $encoding = 'UTF-8')
    {
        $str = mb_ereg_replace('^[\ ]+', '', $str);
        $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) .
            mb_substr($str, 1, mb_strlen($str), $encoding);

        return $str;
    }
}

/**
 * @param $text
 * @param $allowed_tags
 * @return array|string|string[]|null
 */
function plainText($text, $allowed_tags = '<br><p><li>')
{
    $text = strip_tags($text, $allowed_tags);

    return preg_replace('/<[^>]*>/', PHP_EOL, $text);
}

class WordForm
{
    public function __construct(public readonly string $first, public readonly string $second, public readonly string $third)
    {
    }

    public function getCorrectSuffix(int $number): string
    {
        $number = $number % 100;
        if ($number >= 11 && $number <= 19) {
            return $this->third;
        }

        return match ($number % 10) {
            1 => $this->first,
            2, 3, 4 => $this->second,
            default => $this->third,
        };

    }
}


function redirectIfUnauthorized()
{
    if (!Auth::getUser()) {
        return Redirect::to('/');
    }

    return false;
}

function shouldRestrictAdult(): bool
{
    $user = Auth::getUser();

    return !$user || !$user->allowedSeeAdult();
}

function shouldRestrictContent(): bool
{
    $foreign = parse_url(config('app.foreign_url') ?? '');

    return request()->host() !== $foreign['host'] ?? $foreign['path'];
}

?>

