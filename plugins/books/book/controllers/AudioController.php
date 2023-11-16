<?php namespace Books\Book\Controllers;

use Backend\Classes\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AudioController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param Request $request
     * @param int $id
     *
     * @return BinaryFileResponse
     */
    public  function getAudioChunked(Request $request, int $id): BinaryFileResponse
    {
        /**
         * Prevent audio file downloading
         */
        if ($this->isDirectLinkRequested()) {
            abort(404);
        }

        $audioFile = match($id) {
            1 => storage_path() . '/temp/public/sample.mp3',
            2 => storage_path() . '/temp/public/sample.aac',

            default => storage_path() . '/temp/public/sample.aac',
        };

        $response = new BinaryFileResponse($audioFile); //test sample
        BinaryFileResponse::trustXSendfileTypeHeader();

        //$response->setExpires(now());

        return $response;
    }

    /**
     * @return bool
     */
    private function isDirectLinkRequested(): bool
    {
        return ! $this->isChunkedAudioRequested();
    }

    /**
     * @return bool
     */
    private function isChunkedAudioRequested(): bool
    {
        $referrerPresent = (bool) request()->headers->get('referer');
        $rangeHeaderPresent = (bool) request()->headers->get('range');

        return $referrerPresent && $rangeHeaderPresent;
    }

}
