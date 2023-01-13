<?php namespace Books\Book\Models;

use Model;
use Carbon\Carbon;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;

/**
 * Chapter Model
 */
class   Chapter extends Model
{
    use Sortable;
    use Validation;
    use SoftDelete;



    /**
     * @var string table associated with the model
     */
    public $table = 'books_book_chapters';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'title', 'book_id', 'content', 'published_at', 'length', 'sort_order', 'status', 'edition'
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'nullable|string',
        'book_id' => 'required|exists:books_book_books,id',
        'content' => 'nullable|string',
        'published_at' => 'nullable|date',
        'length' => 'nullable|integer',
        'sort_order' => 'sometimes|filled|integer'
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'status' => ChapterStatus::class,
        'edition' => ChapterEdition::class,
    ];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

    /**
     * @var array appends attributes to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array hidden attributes removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'published_at'
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [
        'book' => [Book::class, 'key' => 'id', 'otherKey' => 'book_id']
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];


    public function scopePublished(Builder $query, bool $until_now = true)
    {
        return $query
            ->where('status', ChapterStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->when($until_now, function (Builder $builder) {
                return $builder->where('published_at', '<', Carbon::now());
            });
    }

    public function getIsWillPublishedAttribute(): bool
    {
        return $this->status === ChapterStatus::PUBLISHED && $this->published_at->gt(Carbon::now());
    }

    public static function countChapterLength(string $string): int
    {
        return strlen(strip_tags(preg_replace('/\s+/', '', $string)));
    }


}
