<?php namespace Books\Book\Models;

use Model;
use October\Rain\Database\Traits\Validation;

/**
 * Update Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Update extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_updates';

    /**
     * @var array rules for validation
     */
    public $rules = [];
}
