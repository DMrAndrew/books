<?php namespace Books\Book\Models;

use Db;
use Model;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Relations\HasOneThrough;
use October\Rain\Database\Relations\MorphOne;
use System\Models\File;
use Books\Catalog\Classes\BookTypeEnum;
use October\Rain\Database\Traits\Validation;

/**
 * EbookEdition Model
 *
 *  @method AttachOne fb2
 *  @method HasMany chapters
 *  @method MorphOne edition
 *  @method HasOneThrough book
 */
class EbookEdition extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_ebook_editions';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'price' => 'filled|integer|min:0',
        'free_parts' => 'filled|integer|min:0',
        'download_allowed' => 'boolean',
        'comment_allowed' => 'boolean',
        'sales_free' => 'boolean',
        'fb2' => ['nullable', 'file', 'mimes:xml'],

    ];

    protected $fillable = [
        'download_allowed',
        'comment_allowed',
        'sales_free',
        'free_parts',
        'status',
        'price',
    ];

    protected $casts = [
        'free_parts' => 'integer',
        'price' => 'integer',
        'sales_free' => 'boolean',
        'download_allowed' => 'boolean',
        'comment_allowed' => 'boolean',
        'status' => BookStatus::class,
    ];
    public $hasMany = [
        'chapters' => [Chapter::class, 'key' => 'edition_id', 'otherKey' => 'id'],
    ];

    public $morphOne = [
        'edition' => [Edition::class, 'name' => 'editionable']
    ];

    public $attachOne = [
        'fb2' => File::class
    ];

    public $hasOneThrough = [
        'book' => [
            Book::class,
            'key' => 'editionable_id',
            'through' => Edition::class,
            'throughKey' => 'id',
            'otherKey' => 'id',
            'secondOtherKey' => 'book_id'
        ]
    ];

    public function getRealPriceAttribute()
    {
        return !!$this->sales_free ? 0 : $this->price;
    }

    public static function getEnum(): BookTypeEnum
    {
        return BookTypeEnum::EBook;
    }

    public function nextChapterSortOrder()
    {
        return ($this->chapters()->max('sort_order') ?? 0) + 1;
    }

    public function setSalesAt()
    {
        $this->sales_at = $this->chapters()->published(until_now: false)?->min('published_at') ?? null;
        $this->save();
    }

    public function lengthRecount()
    {
        $this->length = (int)$this->chapters()->sum('length') ?? 0;
        $this->save();
    }

    public function changeChaptersOrder(array $ids, ?array $order = null)
    {
        Db::transaction(function () use ($ids, $order) {
            $order ??= $this->chapters()->pluck('sort_order')->toArray();
            $this->chapters()->first()->setSortableOrder($ids, $order);
            $this->setFreeParts();
        });
    }

    public function setFreeParts()
    {
        Db::transaction(function () {
            $this->chapters()->limit($this->free_parts ?? 0)->update(['sales_type' => ChapterSalesType::FREE]);
            $this->chapters->skip($this->free_parts ?? 0)->each(fn($i) => $i->update(['sales_type' => ChapterSalesType::PAY]));
        });
    }

    public function recompute()
    {
        $this->lengthRecount();
        $this->setSalesAt();
        $this->setFreeParts();
    }
}
