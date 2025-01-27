<?php

namespace Books\Comments\Models;

use App\traits\HasUserScope;
use Books\Blog\Models\Post;
use Books\Book\Models\Book;
use Books\Profile\Models\Profile;
use Books\Videoblog\Models\Videoblog;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Database\Traits\SimpleTree;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use October\Rain\Support\Facades\Event;
use WordForm;

/**
 * Comment Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html\
 *
 * @method HasOne deletedBy
 *
 * @property int deleted_by_id
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

    protected $fillable = ['parent_id', 'user_id', 'content', 'deleted_by_id'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'parent_id' => 'nullable|integer|exists:books_comments_comments,id',
        'user_id' => 'nullable|integer|exists:users,id',
        'deleted_by_id' => 'nullable|integer|exists:books_profile_profiles,id',
        'content' => 'string|min:1',
    ];

    public $morphTo = [
        'commentable' => [],
    ];

    public $hasOne = [
        'deletedBy' => [Profile::class, 'key' => 'id', 'otherKey' => 'deleted_by_id'],
    ];

    protected static function booted()
    {
        static::addGlobalScope('orderByDesc', fn ($q) => $q->orderByDesc('id'));
    }

    public function afterCreate(): void
    {
        // не ответ на чужой комментарий и комментарий к книге
        if (empty($this->parent_id)
            && in_array(get_class($this->commentable), [Book::class, Profile::class, Post::class])
        ) {
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
        return match (true) {
            $this->isDeleted() => 'Удалён',
            $this->isEdited() => 'Редактирован',
            default => ''
        };
    }

    public function isDeleted(): bool
    {
        return (bool) $this->{$this->getDeletedAtColumn()};
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
        return $this->toShort().match (get_class($this->commentable)) {
            Book::class => $this->commentable->title,
            Profile::class => $this->commentable->username,
            Post::class => $this->commentable->title,
            Videoblog::class => $this->commentable->title,
        };
    }

    public function toShort(): string
    {
        return match (get_class($this->commentable)) {
            Book::class => 'к книге ',
            Profile::class => 'в профиле ',
            Post::class => 'к посту ',
            Videoblog::class => 'к видеоблогу ',
        };
    }

    public function toCommentableLink(): string
    {
        return match (get_class($this->commentable)) {
            Book::class => '/book-card/'.$this->commentable->id,
            Profile::class => '/author-page/'.$this->commentable->id,
            Post::class => '/blog/'.$this->commentable->slug,
            Videoblog::class => '/videoblog/'.$this->commentable->slug,
        };
    }

    public function toCommentableTitle()
    {
        return match (get_class($this->commentable)) {
            Book::class => $this->commentable->title,
            Profile::class => $this->commentable->username,
            Post::class => $this->commentable->title,
            Videoblog::class => $this->commentable->title,
        };
    }

    public function scopeWithoutOwner(Builder $builder)
    {
        $profile = match (get_class($this->commentable)) {
            Book::class => $this->commentable->profile()->select((new Profile())->getQualifiedKeyName()),
            Profile::class => [$this->commentable->id],
            Post::class => $this->commentable->profile()->select((new Profile())->getQualifiedKeyName()),
            Videoblog::class => $this->commentable->profile()->select((new Profile())->getQualifiedKeyName()),
            default => null
        };

        return $builder->when($profile, fn ($b) => $b
            ->whereDoesntHave('profile', fn ($p) => $p->whereIn((new Profile())->getQualifiedKeyName(), $profile)));

    }
}
