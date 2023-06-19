<?php namespace Books\Book\Models;

use App\traits\HasUserOrIPScope;
use Books\Book\Classes\ScopeToday;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;

/**
 * AdvertVisits Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class AdvertVisit extends Model
{
    use Validation;
    use HasUserOrIPScope;

    /**
     * @var string table name
     */
    public $table = 'books_book_advert_visits';

    protected $fillable = ['ip', 'user_id', 'advert_id'];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public function scopeToday(Builder $builder): Builder
    {
        return $builder->withGlobalScope('today', new ScopeToday());
    }

}
