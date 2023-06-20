<?php

namespace Books\Book\Models;

use October\Rain\Database\Pivot;
use October\Rain\Database\Traits\Revisionable;
use October\Rain\Database\Traits\Validation;
use System\Models\Revision;

/**
 * BookGenre Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class BookGenre extends Pivot
{
    use Validation;

    public $timestamps = false;
//    protected array $revisionable = ['rate_number'];

    /**
     * @var string table name
     */
    public $table = 'books_book_genre';

    protected $fillable = ['rate_number'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'rate_number' => 'nullable|integer|min:0',
    ];


//    public $morphMany = [
//        'revision_history' => [Revision::class, 'name' => 'revisionable'],
//    ];
}
