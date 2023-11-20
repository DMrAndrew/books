<?php namespace Books\Book\Http;

use Backend\Classes\Controller;
use Books\Book\Classes\Services\AudioFileListenTokenService;
use Illuminate\Http\Response;

class AudioController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * nginx запрашивает разрешение на воспроизведение пользователю аудио файла
     * см. docker/nginx/domain.conf | docker/nginx/domain.production.conf
     *
     * @return Response
     */
    public function allowPlayAudioCheckToken(): Response
    {
        if ($this->tokenIsValid()) {
            return response(null, 200);
        }

        return response(null, 403);
    }

    /**
     * Проверка должна быть быстрая, без запросов к базе данных
     * так как запускается каждый раз в nginx при запросе части аудиофайла
     *
     * @return bool
     */
    private function tokenIsValid(): bool
    {
        $needCheck = config('book.audio.check_token_to_allow_user_download_audio');
        if ( !$needCheck) {
            return true;
        }

        return AudioFileListenTokenService::validateListenTokenForUser();
    }

}
