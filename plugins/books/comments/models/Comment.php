<?php namespace Books\Comments\Models;

use App\traits\ScopeUser;
use Carbon\Carbon;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\SimpleTree;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;

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
        'commentable' => []
    ];

    protected static function booted()
    {
        static::addGlobalScope('orderByDesc', fn($q) => $q->orderByDesc('id'));
    }

    protected function beforeDelete()
    {
        $this->children->each->delete();
    }

    public function isEdited(): bool
    {
        return $this->created_at->notEqualTo($this->updated_at);
    }

    public function scopeRoot(Builder $builder): Builder
    {
        return $builder->where($this->getQualifiedParentColumnName(), '=', null);
    }

    public function dateFormated()
    {
        return $this->updated_at->diffForHumans();
    }

}
