<?php

namespace Books\Comments\Models;

use App\traits\ScopeUser;
use Books\Book\Models\Book;
use Books\Profile\Models\Profile;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\SimpleTree;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
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
    use ScopeUser;

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
        static::addGlobalScope('orderByDesc', fn ($q) => $q->orderByDesc('id'));
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
        return match (get_class($this->commentable)) {
            Book::class => 'к книге '.$this->commentable->title,
            Profile::class => 'в профиле пользователя '.$this->commentable->username,
        };
    }

    public function toCommentableLink(): string
    {
        return match (get_class($this->commentable)) {
            Book::class => '/book-card/',
            Profile::class => '/author-page/',
        }.$this->commentable->id;
    }
}
