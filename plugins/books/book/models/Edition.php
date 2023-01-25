<?php namespace Books\Book\Models;

use Books\Catalog\Classes\BookTypeEnum;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;

/**
 * Edition Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Edition extends Model
{
    use Validation;
    use SoftDelete;

    /**
     * @var string table name
     */
    public $table = 'books_book_editions';

    protected $fillable = ['editionable_id'];
    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $morphTo = [
        'editionable' => []
    ];

    public function scopeEbook(Builder $builder)
    {
        return $builder->where('editionable_type', '=', EbookEdition::class);
    }

    public function getEnum(): BookTypeEnum
    {
        return $this->editionable_type::getEnum();
    }
}
