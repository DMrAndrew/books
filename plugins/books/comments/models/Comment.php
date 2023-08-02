<?php

namespace Books\Comments\Models;

use App\traits\HasUserScope;
use Books\Blog\Models\Post;
use Books\Book\Models\Book;
use Books\Profile\Models\Profile;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\SimpleTree;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use October\Rain\Support\Facades\Event;
use WordForm;

/**
 * Comment Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Comment extends Model
{
    use Validation;
    use SimpleTree;
    use SoftDelete;
    use HasUserScope;

    /**
     * @var string table name
     */
    public $table = 'books_comments_comments';

    protected $fillable = ['parent_id', 'user_id', 'content'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'parent_id' => 'nullable|integer|exists:books_comments_comments,id',
        'user_id' => 'nullable|integer|exists:users,id',
        'content' => 'string|min:1',
    ];

    public $morphTo = [
        'commentable' => [],
    ];

    protected static function booted()
    {
        static::addGlobalScope('orderByDesc', fn($q) => $q->orderByDesc('id'));
    }

    /**
     * @return void
     */
    public function afterCreate(): void
    {
        // не ответ на чужой комментарий и комментарий к книге
        if (empty($this->parent_id) && get_class($this->commentable) === Book::class) {
            Event::fire('books.comments::comment.created', [$this]);
        }

        // ответ на комментарий, неважно в профиле или книге
        if ($this->parent_id) {
            Event::fire('books.comments::comment.replied', [$this]);
        }
    }

    public function isEdited(): bool
    {
        return $this->created_at->notEqualTo($this->updated_at);
    }

    public function addition(): string
    {
        return $this->isDeleted() ? 'Удалён' : ($this->isEdited() ? 'Редактирован' : '');
    }

    public function isDeleted(): bool
    {
        return (bool)$this->{$this->getDeletedAtColumn()};
    }

    public function scopeRoot(Builder $builder): Builder
    {
        return $builder->where($this->getQualifiedParentColumnName(), '=', null);
    }

    public function dateFormated()
    {
        return $this->updated_at->diffForHumans();
    }

    public function replayWordForm(): WordForm
    {
        return new WordForm(...['ответ', 'ответа', 'ответов']);
    }

    public function toShortString(): string
    {
        return match (get_class($this->commentable)) {
            Book::class => 'к книге ' . $this->commentable->title,
            Profile::class => 'в профиле пользователя ' . $this->commentable->username,
            Post::class => 'к посту ' . $this->commentable->title,
        };
    }

    public function toCommentableLink(): string
    {
        return match (get_class($this->commentable)) {
                Book::class => '/book-card/' . $this->commentable->id,
                Profile::class => '/author-page/'. $this->commentable->id,
                Post::class => '/blog/'. $this->commentable->slug,
            };
    }

    public function scopeWithoutOwner(Builder $builder)
    {
        $profile = match (get_class($this->commentable)) {
            Book::class => $this->commentable->profile()->select((new Profile())->getQualifiedKeyName()),
            Profile::class => [$this->commentable->id],
            Post::class => $this->commentable->profile()->select((new Profile())->getQualifiedKeyName()),
            default => null
        };

        return $builder->when($profile, fn($b) => $b
            ->whereDoesntHave('profile', fn($p) => $p->whereIn((new Profile())->getQualifiedKeyName(), $profile)));

    }
}
