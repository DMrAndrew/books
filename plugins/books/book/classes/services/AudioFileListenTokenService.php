<?php
declare(strict_types=1);

namespace Books\Book\Classes\Services;

use Books\Book\Classes\Contracts\iAudioFileListenTokenService;
use Cache;
use Cookie;
use Request;
use Str;

/**
 * При запросе страницы с аудиофайлами
 *  - на стороне php генерируем пару [токен => страница]
 *  - сохраняем пару в кеш бекэнда и в куки пользователю
 * (если пользователь имеет доступ к странице, значит имеет доступ к аудиофайлам)
 *
 * При скачивании аудиофайла через nginx проверяем доступ через ключ
 */
class AudioFileListenTokenService implements iAudioFileListenTokenService
{
    const CACHE_TTL_SECONDS = 24 * 60 * 60;
    const CACHE_TTL_MINUTES = 24 * 60;

    const LISTEN_TOKEN_KEY = 'listen_audio_token_';

    /**
     * Генерирует токен на прослушивание аудиофайлов на странице
     * Сохраняет в кеш для хранения на сервере и в куки для хранения у пользователя
     *
     * @return void
     */
    public static function generateListenTokenForUser(): void
    {
        $page = Request::getUri();
        $pagePath = parse_url($page, PHP_URL_PATH);

        $tokenUuid = Str::uuid(); // для каждого пользователя

        /**
         * Сохраняем в кеш для последующей проверки токена
         */
        Cache::put($tokenUuid, $pagePath, self::CACHE_TTL_SECONDS);

        /**
         * Сохраняем в куки пользователю
         */
        Cookie::queue(self::LISTEN_TOKEN_KEY, $tokenUuid, minutes: self::CACHE_TTL_MINUTES);
    }

    /**
     * Проверяет токен на прослушивание аудиофайла на странице
     * Проверка должна быть быстрая, без запросов к базе данных
     * так как запускается каждый раз в nginx при запросе части аудиофайла
     *
     * Сравнивает наличие в кеше пары [token => страница] которые берем из cookie
     *
     * @return bool
     */
    public static function validateListenTokenForUser(): bool
    {
        $listenAudioToken = Cookie::get(self::LISTEN_TOKEN_KEY);
        if ( !$listenAudioToken) {
            return false;
        }

        $pageWithAudio = Request::header('referer');
        if (null == $pageWithAudio) {
            return false;
        }
        $pagePathWithAudio = parse_url($pageWithAudio, PHP_URL_PATH);

        $pageFromCache = Cache::get($listenAudioToken);

        return $pageFromCache === $pagePathWithAudio;
    }
}