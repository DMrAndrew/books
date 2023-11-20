<?php
declare(strict_types=1);

use Books\Book\Http\AudioController;

Route::get('allow-play-audio-check-token', [AudioController::class, 'allowPlayAudioCheckToken']);
