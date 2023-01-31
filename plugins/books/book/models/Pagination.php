<?php namespace Books\Book\Models;

use Model;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Database\Traits\Validation;

/**
 * Pagination Model
 *
 * @method HasOne chapter
 */
class Pagination extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_pagination';

    protected $fillable = [
        'page',
        'length',
        'content',
        'chapter_id'
    ];

    protected $casts = [
        'content' => 'string'
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'page' => 'required|integer',
        'length' => 'integer',
        'content' => 'nullable|string',
        'chapter_id' => 'required|integer|exists:books_book_chapters,id'
    ];

    public $belongsTo = [
        'chapter' => [Chapter::class, 'key' => 'id', 'otherKey' => 'chapter_id']
    ];

    public function file()
    {
        return $this->chapter()->first()?->file();
    }
}

