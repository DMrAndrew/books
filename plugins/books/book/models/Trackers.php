<?php namespace Books\Book\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * Trackers Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Trackers extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_trackers';

    protected $fillable = ['sec', 'length', 'user_id', 'paginator_id'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'sec' => 'integer',
        'length' => 'integer',
    ];

    public $belongsTo = [
        'user' => [User::class, 'key' => 'id', 'otherKey' => 'user_id'],
        'paginator' => [Pagination::class, 'key' => 'id', 'otherKey' => 'paginator_id'],
    ];
}
