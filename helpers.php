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

function getCorrectSuffix($number, $endingArray)
{
    $number = $number % 100;
    if ($number >= 11 && $number <= 19) {
        $ending = $endingArray[2];
    } else {
        $i = $number % 10;
        switch ($i) {
            case (1):
                $ending = $endingArray[0];
                break;
            case (2):
            case (3):
            case (4):
                $ending = $endingArray[1];
                break;
            default:
                $ending = $endingArray[2];
        }
    }
    return $ending;
}

function redirectIfUnauthorized()
{
    if (!Auth::getUser()) {
        return Redirect::to('/');
    }
    return false;
}

?>

