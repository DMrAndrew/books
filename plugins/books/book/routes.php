<?php
declare(strict_types=1);

use Books\Book\Controllers\AudioController;

Route::get('audio/{id}', [AudioController::class, 'getAudioChunked'])->name('audio.listen.public');
