<?php namespace Books\Book\Models;

use App\traits\HasUserIPScopes;
use Model;
use October\Rain\Database\Traits\Validation;

/**
 * Downloads Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Downloads extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_downloads';

    /**
     * @var array rules for validation
     */
    public $rules = [];

}
