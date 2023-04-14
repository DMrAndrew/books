<?php

namespace Books\Book\Models;

use Books\Profile\Models\Profile;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use October\Rain\Support\Facades\Event;
use RainLab\User\Facades\Auth;

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

    public const ACCEPTED = 'accepted';

    /**
     * @var string table name
     */
    public $table = 'books_book_authors';

    protected $fillable = ['book_id', 'profile_id', 'percent', 'sort_order', 'is_owner', 'accepted'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'book_id' => 'required|exists:books_book_books,id',
        'profile_id' => 'required|exists:books_profile_profiles,id',
        'percent' => 'integer|min:0|max:100',
        'is_owner' => 'boolean',
        'sort_order' => 'integer',
        'accepted' => 'nullable|boolean',
    ];

    protected $casts = [
        'percent' => 'integer',
        'is_owner' => 'boolean',
    ];

    public $belongsTo = [
        'book' => [Book::class, 'key' => 'book_id', 'otherKey' => 'id'],
        'profile' => [Profile::class, 'key' => 'profile_id', 'otherKey' => 'id'],
    ];

    /**
     * @return void
     */
    public function afterCreate(): void
    {
        if (!$this->is_owner) {
            Event::fire('books.book::author.invited', [$this, Auth::getUser()?->profile]);
        }
    }

    public function scopeOwner(Builder $builder, $value = true): Builder
    {
        return $builder->when(is_bool($value), fn ($b) => $b->where('is_owner', '=', $value));
    }

    public function scopeCoAuthors(Builder $builder): Builder
    {
        return $builder->whereNull('is_owner');
    }

    public function scopeAccepted(Builder $builder): Builder
    {
        return $builder->where('accepted', '=', true);
    }

    public function scopeRejected(Builder $builder): Builder
    {
        return $builder->where('accepted', '=', false);
    }

    public function scopeAwait(Builder $builder): Builder
    {
        return $builder->where('accepted', '=', null);
    }

    public function scopeSortByAuthorOrder(Builder $builder): Builder
    {
        return $builder->orderByDesc('sort_order');
    }
}
