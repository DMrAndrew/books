<?php namespace Books\Book\Models;

use Model;

/**
 * Promocode Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Promocode extends Model
{
    use \October\Rain\Database\Traits\Validation;

    const CODE_LENGTH = 8;

    /**
     * @var string table name
     */
    public $table = 'books_book_promocodes';

    /**
     * @var array rules for validation
     */
    public $rules = [];
}
