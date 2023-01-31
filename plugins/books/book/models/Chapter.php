<?php namespace Books\Book\Models;

use DiDom\Document;
use Illuminate\Support\Collection;
use Model;
use Carbon\Carbon;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use RecursiveIteratorIterator;

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
        'title', 'edition_id', 'content', 'published_at', 'length', 'sort_order', 'status', 'sales_type'
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'nullable|string',
        'edition_id' => 'required|exists:books_book_ebook_editions,id',
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
        'sales_type' => ChapterSalesType::class,
    ];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

    public $belongsTo = [
        'edition' => [EbookEdition::class, 'key' => 'id', 'otherKey' => 'edition_id']
    ];

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
        return strlen(strip_tags(preg_replace('/\s+/', '', $string))) ?? 0;
    }

    public function paginator()
    {

        $dom = (new \DOMDocument());
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($this->content, 'HTML-ENTITIES', 'UTF-8'));
        $root = $dom->getElementsByTagName('body')[0];
        $perhapses = collect($root->childNodes)->map(fn($node) => [
            'html' => $dom->saveHTML($node),
            'length' => strlen($node->textContent),
        ]);
        $pagination = collect([collect([])]);

        foreach ($perhapses as $perhaps) {

            $length = ($pagination->last()?->sum('length') ?? 0) + $perhaps['length'];
            if ($length >= 6500 && $length <= 7500) {
                $pagination->push(collect([]));
            }
            $pagination->last()->push($perhaps);
        }
        return $pagination->filter(fn($i) => $i->sum('length'))->map->sum('length');

    }

}
