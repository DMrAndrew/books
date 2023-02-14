<?php

namespace Books\Book\Models;


use Books\Book\Classes\EditionService;
use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\ChapterSalesType;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Jobs\JobProgress;
use Db;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Traits\Revisionable;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use Queue;
use RainLab\User\Models\User;
use System\Models\File;

/**
 * Edition Model
 *
 * * @method HasMany chapters
 * * @method AttachOne fb2
 * * @method BelongsTo book
 * * @property  Book book
 */
class Edition extends Model
{
    use Validation;
    use SoftDelete;
    use Revisionable;

    /**
     * @var string table name
     */
    public $table = 'books_book_editions';

    protected $revisionable = ['length'];
    public string $trackerChildRelation = 'chapters';

    protected $fillable = [
        'type',
        'download_allowed',
        'comment_allowed',
        'sales_free',
        'free_parts',
        'status',
        'price',
        'book_id',
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
        'fb2' => ['nullable', 'file', 'mimes:xml'],
    ];

    public $hasMany = [
        'chapters' => [Chapter::class, 'key' => 'edition_id', 'otherKey' => 'id'],
    ];

    public $attachOne = [
        'fb2' => File::class,
    ];

    public $belongsTo = [
        'book' => [Book::class, 'key' => 'book_id', 'otherKey' => 'id'],
    ];

    public function service(): EditionService
    {
        return new EditionService($this);
    }

    public function shouldRevision(): bool
    {
        return false;
    }

    protected function beforeUpdate()
    {
        $this->revisionsEnabled = $this->shouldRevision();
    }


    public function scopeWithProgress(Builder $builder, User $user): Builder
    {
        return $builder->withSum(['trackers as progress' => fn($trackers) => $trackers->user($user)], 'progress');
    }

    public function getRealPriceAttribute()
    {
        return (bool)$this->sales_free ? 0 : $this->price;
    }

    public function scopeMinPrice(Builder $builder, ?int $price): Builder
    {
        return $builder->where('price', '>=', $price);
    }

    public function scopeMaxPrice(Builder $builder, ?int $price): Builder
    {
        return $builder->where('price', '<=', $price);
    }
    public function scopeFree(Builder $builder, $free = true): Builder
    {
        return $builder->where('sales_free', '=', $free)->orWhere('price','=',0);
    }

    public function scopeEbook(Builder $builder): Builder
    {
        return $builder->where('type', '=', EditionsEnums::Ebook->value);
    }

    public function nextChapterSortOrder()
    {
        return ($this->chapters()->max('sort_order') ?? 0) + 1;
    }

    public function lengthRecount()
    {
        $this->chapters()->get()->each->lengthRecount();
        $this->length = (int)$this->chapters()->sum('length');
        $this->save();
    }

    public function changeChaptersOrder(array $ids, ?array $order = null)
    {
        Db::transaction(function () use ($ids, $order) {
            $order ??= $this->chapters()->pluck('sort_order')->toArray();
            $this->chapters()->first()->setSortableOrder($ids, $order);
            $this->setFreeParts();
            $this->chapters()->get()->each->setNeighbours();
        });
    }

    public function setFreeParts()
    {
        Db::transaction(function () {
            $this->chapters()->limit($this->free_parts)->update(['sales_type' => ChapterSalesType::FREE]);
            $this->chapters->skip($this->free_parts)->each->update(['sales_type' => ChapterSalesType::PAY]);
        });
    }

    public function paginateContent()
    {
        $this->chapters()->get()->each->paginateContent(true);
    }
}
