<?php namespace Books\Book\Models;


use Model;
use October\Rain\Database\Traits\Validation;

/**
 * AwardBook Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class AwardBook extends Model
{
    use Validation;


    /**
     * @var string table name
     */
    public $table = 'books_book_award_books';

    protected $fillable = ['user_id', 'book_id', 'award_id'];

    public $hasOne = [
        'award' => [Award::class, 'key' => 'id', 'otherKey' => 'award_id'],
        'book' => [Book::class, 'key' => 'id', 'otherKey' => 'book_id']
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [];

}
