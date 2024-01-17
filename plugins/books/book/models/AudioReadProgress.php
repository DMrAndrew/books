<?php namespace Books\Book\Models;

use App\traits\HasUserScope;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;

/**
 * AudioReadProgress Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class AudioReadProgress extends Model
{
    use Validation;
    use HasUserScope;

    const SAVE_USER_AUDIO_READ_PROGRESS_STEP_SECONDS = 3; // минимальный прогресс для сохранения
    const SAVE_USER_AUDIO_READ_PROGRESS_TIMEOUT_SECONDS = 5; // сохраняем прогресс каждые хх секунд

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

    /**
     * Start to save progress after x seconds of playing
     *
     * @return int
     */
    public static function getStartSavingUserReadProgressAfterDelay(): int
    {
        return (int) config('book.audio.save_user_audio_read_pregress_delay_in_seconds');
    }

    /**
     * Save audio read progress each x seconds
     *
     * @return int
     */
    public static function getSaveUserReadProgressStep(): int
    {
        return (int) config('book.audio.save_user_audio_read_pregress_timeout_in_seconds');
    }

    public function scopeBook(Builder $builder, ?Book $book = null): Builder
    {
        return $builder->where($this->qualifyColumn('book_id'), '=', $book?->id);
    }

    public function scopeChapter(Builder $builder, ?Chapter $chapter = null): Builder
    {
        return $builder->where($this->qualifyColumn('chapter_id'), '=', $chapter?->id);
    }
}
