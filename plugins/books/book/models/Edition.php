<?php namespace Books\Book\Models;

use Db;
use Model;
use System\Models\File;
use October\Rain\Database\Builder;
use Books\Book\Classes\Enums\EditionsEnums;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Relations\AttachOne;

/**
 * Edition Model
 *
 * * @method HasMany chapters
 * * @method AttachOne fb2
 * * @method AttachOne audio
 * * @method AttachOne picture
 */
class Edition extends Model
{
    use Validation;
    use SoftDelete;

    /**
     * @var string table name
     */
    public $table = 'books_book_editions';

    protected $fillable = [
        'type',
        'toggled_hidden',
        'toggled_free',
        'download_allowed',
        'comment_allowed',
        'sales_free',
        'free_parts',
        'status',
        'price',
        'book_id'
    ];

    protected $casts = [
        'type' => EditionsEnums::class,
        'free_parts' => 'integer',
        'price' => 'integer',
        'sales_free' => 'boolean',
        'download_allowed' => 'boolean',
        'comment_allowed' => 'boolean',
        'status' => BookStatus::class,
    ];
    /**
     * @var array rules for validation
     */
    public $rules = [
        'type' => 'required|integer',
        'price' => 'filled|integer|min:0|max:9999',
        'free_parts' => 'filled|integer|min:0',
        'download_allowed' => 'boolean',
        'comment_allowed' => 'boolean',
        'sales_free' => 'boolean',
        'files.*' => ['nullable', 'file', 'mimes:xml,mp3,mp4,jpeg,jpg,png'],
        'toggled_hidden' => 'boolean',
        'toggled_free' => 'boolean',
    ];

    public $hasMany = [
        'chapters' => [Chapter::class, 'key' => 'edition_id', 'otherKey' => 'id'],
    ];

    public $attachOne = [
        'fb2' => File::class,
        'audio' => File::class,
        'picture' => File::class,
    ];

    public $belongsTo = [
        'book' => [Book::class, 'key' => 'book_id', 'otherKey' => 'id']
    ];

    public function getRealPriceAttribute()
    {
        return !!$this->sales_free ? 0 : $this->price;
    }

    public function scopeEbook(Builder $builder)
    {
        return $builder->where('type', '=', EditionsEnums::Ebook->value);
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
        $this->setSalesAt();
        $this->setFreeParts();
    }

}
