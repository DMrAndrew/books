<?php namespace Books\Book\Models;

use Model;
use Books\Profile\Models\Profile;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Relations\BelongsTo;

/**
 * Author Model
 *
 * @method BelongsTo book
 * @method BelongsTo profile
 */
class Author extends Model
{
    use Sortable;
    use SoftDelete;
    use Validation;

    public const IS_OWNER = 'is_owner';
    public const PERCENT = 'percent';
    public const PROFILE_ID = 'profile_id';

    /**
     * @var string table name
     */
    public $table = 'books_book_authors';


    protected $fillable = ['book_id', 'profile_id', 'percent','sort_order','is_owner'];
    /**
     * @var array rules for validation
     */
    public $rules = [
        'book_id' => 'required|exists:books_book_books,id',
        'profile_id' => 'required|exists:books_profile_profiles,id',
        'percent' => 'integer|min:0|max:100',
        'is_owner' => 'boolean',
        'sort_order' => 'integer'
    ];

    protected $casts = [
        'percent' => 'integer',
        'is_owner' => 'boolean'
    ];

    public $belongsTo = [
        'book' => [Book::class,'key' => 'book_id','otherKey' => 'id'],
        'profile' => [Profile::class,'key' => 'profile_id','otherKey' => 'id'],
    ];

    public function scopeOwner(Builder $builder): Builder
    {
        return $builder->where('is_owner','=',true);
    }

    public function scopeNotOwner(Builder $builder): Builder
    {
        return $builder->where('is_owner','=',false);
    }


}
