<?php

namespace Books\Book\Models;


use Books\Book\Classes\EditionService;
use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\ChapterSalesType;
use Books\Book\Classes\Enums\EditionsEnums;
use Carbon\Carbon;
use Db;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Traits\Revisionable;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;
use System\Models\File;
use System\Models\Revision;

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

    protected $revisionable = ['length', 'status'];
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
        'free_parts' => 'filled|integer',
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

    public $morphMany = [
        'revision_history' => [Revision::class, 'name' => 'revisionable']
    ];

    public function service(): EditionService
    {
        return new EditionService($this);
    }

    public function editAllowed(): bool
    {
        return !$this->isPublished() || $this->status === BookStatus::WORKING || $this->sales_free;
    }

    public function isPublished(): bool
    {
        return !!$this->sales_at;
    }

    public function setPublishAt()
    {
        $this->attributes['sales_at'] = Carbon::now();
    }

    public function getAllowedStatusCases(): array
    {
        $cases = collect(BookStatus::publicCases());

        if ($this->status === BookStatus::WORKING && $this->hasSales()) {
            $cases = $cases->forget(BookStatus::HIDDEN); //нельзя перевести в статус "Скрыто" если куплена хотя бы 1 раз
        }
        if ($this->status === BookStatus::FROZEN) {
            $cases = collect();
        }

        if ($this->status === BookStatus::COMPLETE) {
            $cases = $cases->only(BookStatus::HIDDEN->value); // Из “Завершено” можем перевести только в статус “Скрыто”.
        }
        $cases[$this->status->value] = $this->status;

        return $cases->toArray();
    }


    public function shouldDeferredUpdate(): bool
    {
        return $this->status === BookStatus::COMPLETE;
    }

    public function hasSales()
    {
        return false;
    }

    public function shouldRevision(): bool
    {
        return false;
    }

    protected function beforeUpdate()
    {
        $this->revisionsEnabled = $this->shouldRevision();
    }

    protected function afterUpdate()
    {
        if ($this->wasChanged('free_parts')) {
            $this->setFreeParts();
        }
    }


    public function scopeWithProgress(Builder $builder, User $user): Builder
    {
        return $builder->withMax(['trackers as progress' => fn($trackers) => $trackers->user($user)->withoutTodayScope()], 'progress');
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
        return $builder->where('sales_free', '=', $free);
    }

    public function scopeEbook(Builder $builder): Builder
    {
        return $builder->type(EditionsEnums::Ebook);
    }

    public function scopeAudio(Builder $builder): Builder
    {
        return $builder->type(EditionsEnums::Audio);
    }

    public function scopePhysic(Builder $builder): Builder
    {
        return $builder->type(EditionsEnums::Physic);
    }

    public function scopeComics(Builder $builder): Builder
    {
        return $builder->type(EditionsEnums::Comics);
    }


    public function scopeType(Builder $builder, EditionsEnums $type): Builder
    {
        return $builder->where('type', '=', $type->value);
    }

    public function scopeStatus(Builder $builder, BookStatus $status): Builder
    {
        return $builder->where('status', '=', $status->value);
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
            $this->chapters()->get()->each->setNeighbours();
            $this->setFreeParts();
        });
    }

    public function setFreeParts()
    {
        Db::transaction(function () {
            $this->chapters()->limit($this->free_parts)->update(['sales_type' => ChapterSalesType::FREE]);
//            $this->chapters()->offset($this->free_parts); ошибка?
            $this->chapters()->get()->skip($this->free_parts)->each->update(['sales_type' => ChapterSalesType::PAY]);
        });
    }

    public function paginateContent()
    {
        $this->chapters()->get()->each->paginateContent(true);
    }
}
