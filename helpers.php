<?php

/**
 * проверяем, что функция mb_ucfirst не объявлена
 * и включено расширение mbstring (Multibyte String Functions)
 */

use Books\Book\Models\Book;
use Books\User\Classes\CookieEnum;
use Books\User\Classes\UserService;
use Carbon\CarbonInterval;
use RainLab\User\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

if (!function_exists('mb_ucfirst') && extension_loaded('mbstring')) {
    /**
     * mb_ucfirst - преобразует первый символ в верхний регистр
     *
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
    return !UserService::allowedSeeAdult();
}

function shouldRestrictContent(): bool
{
    return !isComDomainRequested();
}

function isComDomainRequested(): bool
{
    $com = parse_url(comDomain() ?? '');
    return request()->host() === ($com['host'] ?? $com['path']);
}

function comDomain(): ?string
{
    return config('app.com_url') ?? null;
}

function getFreqString(int $count, int $days): string
{
    return $count
        . ' '
        . (new WordForm(...['раз', 'раза', 'раз']))->getCorrectSuffix($count)
        . ' в '
        . str_replace('неделя', 'неделю', CarbonInterval::days($days)
            ->cascade()
            ->forHumans(['parts' => 1, 'aUnit' => true]));
}

function getUnlovedFromCookie(): array
{
    return Cookie::has(CookieEnum::UNLOVED_GENRES->value) ?
        json_decode(Cookie::get(CookieEnum::UNLOVED_GENRES->value))
        : [];
}

function getLovedFromCookie(): array
{
    return Cookie::has(CookieEnum::LOVED_GENRES->value) ?
        json_decode(Cookie::get(CookieEnum::LOVED_GENRES->value))
        : [];
}

/**
 * @throws NotFoundHttpException
 */
function askAboutAdult(Book $book): bool
{
    return restrictProhibited($book) &&
        (($book->isAdult() && UserService::canBeAskedAdultPermission()) || abort(404));
}

/**
 * @throws NotFoundHttpException
 */
function restrictProhibited(Book $book): bool
{
    return (isComDomainRequested() && $book->isProhibited()) ? abort(404) : true;
}

?>

