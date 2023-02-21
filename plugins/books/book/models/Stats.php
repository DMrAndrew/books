<?php namespace Books\Book\Models;

use Model;
use October\Rain\Database\Traits\Revisionable;
use October\Rain\Database\Traits\Validation;
use System\Models\Revision;

/**
 * Stats Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Stats extends Model
{
    use Validation;
    use Revisionable;

    /**
     * @var string table name
     */
    public $table = 'books_book_stats';

    protected $revisionable = ['rate'];

    protected $fillable = ['likes_count','in_lib_count','read_count','rate','book_id','comments_count'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'likes_count' => 'nullable|integer|min:0',
        'in_lib_count' => 'nullable|integer|min:0',
        'read_count' => 'nullable|integer|min:0',
        'rate' => 'nullable|integer|min:0',
        'comments_count' => 'nullable|integer|min:0',
    ];

    public $belongsTo = [
        'book' => [Book::class,'key' => 'id','otherKey' => 'book_id']
    ];

    public $morphMany = [
        'revision_history' => [Revision::class, 'name' => 'revisionable']
    ];


}
