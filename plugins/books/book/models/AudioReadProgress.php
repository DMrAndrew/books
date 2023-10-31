<?php namespace Books\Book\Models;

use Model;
use October\Rain\Database\Traits\Validation;

/**
 * AudioReadProgress Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class AudioReadProgress extends Model
{
    use Validation;

    const SAVE_USER_AUDIO_READ_PROGRESS_STEP_SECONDS = 2;
    const SAVE_USER_AUDIO_READ_PROGRESS_TIMEOUT_SECONDS = 1;

    public $table = 'books_book_audio_read_progresses';

    public $rules = [
        'user_id' => 'required|exists:users,id',
        'book_id' => 'required|exists:books_book_books,id',
        'chapter_id' => 'required|exists:books_book_chapters,id',
        'progress' => 'integer|min:0',
    ];

    public $fillable = [
        'user_id',
        'book_id',
        'chapter_id',
        'progress',
    ];
}
