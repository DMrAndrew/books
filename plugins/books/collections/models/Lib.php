<?php

namespace Books\Collections\Models;

use Books\Book\Models\Book;
use Books\Collections\classes\CollectionEnum;
use Illuminate\Database\Eloquent\Prunable;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;

/**
 * Collection Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Lib extends Model
{
    use Validation;
    use Prunable;

    /**
     * @var string table name
     */
    public $table = 'books_collections_lib';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    protected $fillable = ['type', 'loved', 'book_id'];

    public $belongsTo = [
        'book' => [Book::class],
    ];

    protected $casts = [
        'type' => CollectionEnum::class,
        'loved' => 'boolean',
    ];

    public function scopePublic(Builder $builder): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return $builder->whereHas('book', fn ($book) => $book->public());
    }

    public function scopeBook(Builder $builder, Book $book): Builder
    {
        return $builder->where('book_id', '=', $book->id);
    }

    public function scopeType(Builder $builder, ?CollectionEnum ...$type): Builder
    {
        return $builder->whereIn('type', collect($type)->pluck('value')->toArray());
    }

    public function scopeNotWatched(Builder $builder)
    {
        return $builder->whereNot('type', '=', CollectionEnum::WATCHED);
    }

    public function prunable()
    {
        return static::query()
            ->whereDate('created_at', '<=', today()->copy()->subWeeks(2))
            ->type(CollectionEnum::WATCHED);
    }

    protected function beforeDelete()
    {
        $this->favorites()->delete();
    }
}
